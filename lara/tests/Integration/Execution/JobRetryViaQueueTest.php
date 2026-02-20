<?php

namespace Tests\Integration\Execution;

use App\Jobs\RetryJob;
use Core\Execution\JobDispatcherInterface;
use Core\Job\Job;
use Core\Pipeline\Pipeline;
use Core\Pipeline\PipelineContext;
use Core\Pipeline\PipelineError;
use Core\Pipeline\PipelineResult;
use Core\ValueObject\JobId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Orchestra\Testbench\TestCase;

final class JobRetryViaQueueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/../../../database/migrations');
    }

    protected function getPackageProviders($app)
    {
        return [
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
        $app['config']->set('queue.default', 'sync');
    }

    public function test_failed_job_is_retried_via_laravel_queue(): void
    {
        // Arrange
        Queue::fake();

        $dispatcher = $this->app->make(JobDispatcherInterface::class);

        $job = new Job(
            new JobId('retry-1'),
            new class implements Pipeline {

                public function name(): string
                {
                    return 'failing';
                }

                public function run(PipelineContext $context): PipelineResult
                {
                    return PipelineResult::failure(
                        new PipelineError('failed', 'boom')
                    );
                }
            }
        );

        $dispatcher->dispatch($job);
        
        Queue::assertPushed(RetryJob::class, function (RetryJob $queued) use ($job) {
            return $queued->command->jobId() === $job->id()->value()
                && $queued->command->attempt() === 2;
        });

    }
}
