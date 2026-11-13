<?php

namespace Tests\Integration\Execution;

use App\Execution\QueuedJobSubmission;
use App\Pipeline\LaravelPipelineFactory;
use Core\Job\Job;
use Core\Pipeline\Pipeline;
use Core\Pipeline\PipelineContext;
use Core\Pipeline\PipelineFactory;
use Core\Pipeline\PipelineResult;
use Core\ValueObject\JobId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase;

final class DatabaseQueueExecutionTest extends TestCase
{
    use RefreshDatabase;

    private string $projectPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/../../../database/migrations');

        $this->projectPath = sys_get_temp_dir() . '/laradape-hop-' . uniqid();
        mkdir($this->projectPath . '/src', 0777, true);
        file_put_contents($this->projectPath . '/src/legacy.php', <<<'PHP'
<?php
$value = $text{0};
PHP);
        file_put_contents($this->projectPath . '/composer.json', json_encode([
            'require' => ['php' => '^7.4'],
        ]));
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->projectPath);
        parent::tearDown();
    }

    protected function getPackageProviders($app)
    {
        return [
            \App\Providers\AppServiceProvider::class,
            \App\Providers\ExecutionServiceProvider::class,
            \App\Providers\PipelineServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('cache.default', 'array');
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('queue.default', 'database');
    }

    public function test_database_queue_worker_executes_and_completes_job(): void
    {
        $this->app->singleton(PipelineFactory::class, function () {
            return new LaravelPipelineFactory([
                'database-test' => fn () => new class implements Pipeline {
                    public function name(): string
                    {
                        return 'database-test';
                    }

                    public function run(PipelineContext $context): PipelineResult
                    {
                        return PipelineResult::success();
                    }
                },
            ]);
        });

        $job = new Job(
            new JobId('database-queue-1'),
            $this->app->make(PipelineFactory::class)->make('database-test')
        );

        $this->app->make(QueuedJobSubmission::class)->submit($job);

        $this->assertDatabaseHas('execution_jobs', [
            'id' => 'database-queue-1',
            'status' => 'pending',
        ]);
        $this->assertDatabaseCount('jobs', 1);

        $this->artisan('queue:work', ['--once' => true])
            ->assertExitCode(0);

        $this->assertDatabaseHas('execution_jobs', [
            'id' => 'database-queue-1',
            'status' => 'completed',
        ]);
        $this->assertDatabaseCount('jobs', 0);
    }

    public function test_database_queue_executes_upgrade_hops_in_order(): void
    {
        $jobId = new JobId('database-hop-1');
        $payload = [
            'project_path' => $this->projectPath,
            'from' => '7.4',
            'to' => '8.1',
            'dry_run' => false,
            'options' => [
                'engine' => 'regex',
                'ai' => 'off',
                'exakat' => 'off',
            ],
        ];

        $this->app->make(\App\Execution\PupgraderHopScheduler::class)
            ->submitInitial($jobId, $payload);

        $this->artisan('queue:work', ['--once' => true])->assertExitCode(0);

        $this->assertDatabaseHas('execution_jobs', [
            'id' => 'database-hop-1',
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('execution_jobs', [
            'id' => 'database-hop-1:hop-1',
            'status' => 'pending',
        ]);

        $nextPayload = json_decode(
            (string) $this->app['db']->table('execution_jobs')
                ->where('id', 'database-hop-1:hop-1')
                ->value('payload'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        $this->assertSame('database-hop-1', $nextPayload['root_job_id']);
        $this->assertSame('database-hop-1', $nextPayload['parent_job_id']);
        $this->assertSame(1, $nextPayload['hop_index']);
        $this->assertSame(2, $nextPayload['hop_count']);

        $nextId = $this->app->make(\App\Execution\PupgraderHopScheduler::class)
            ->scheduleNext($jobId);
        $this->assertSame('database-hop-1:hop-1', $nextId?->value());
        $this->assertSame(2, $this->app['db']->table('execution_jobs')->count());

        $this->artisan('queue:work', ['--once' => true])->assertExitCode(0);

        $this->assertDatabaseHas('execution_jobs', [
            'id' => 'database-hop-1:hop-1',
            'status' => 'completed',
        ]);
        $this->assertStringContainsString(
            '$text[0]',
            file_get_contents($this->projectPath . '/src/legacy.php')
        );
    }

    public function test_cancelled_upgrade_chain_is_not_executed(): void
    {
        $jobId = new JobId('database-cancel-1');
        $this->app->make(\App\Execution\PupgraderHopScheduler::class)
            ->submitInitial($jobId, [
                'project_path' => $this->projectPath,
                'from' => '7.4',
                'to' => '8.1',
                'dry_run' => false,
                'options' => [
                    'engine' => 'regex',
                    'ai' => 'off',
                    'exakat' => 'off',
                ],
            ]);

        $chain = $this->app->make(\App\Execution\JobChainDetailsReader::class)
            ->find($jobId->value());
        $this->app->make(\App\Execution\JobChainCanceller::class)->cancel($chain);

        $this->artisan('queue:work', ['--once' => true])->assertExitCode(0);

        $this->assertDatabaseHas('execution_jobs', [
            'id' => $jobId->value(),
            'status' => 'cancelled',
        ]);
        $this->assertDatabaseMissing('execution_jobs', [
            'id' => $jobId->value().':hop-1',
        ]);
        $this->assertStringContainsString(
            '$text{0}',
            file_get_contents($this->projectPath . '/src/legacy.php')
        );
    }

    private function removeDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        foreach (scandir($directory) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $entry;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }

        rmdir($directory);
    }
}
