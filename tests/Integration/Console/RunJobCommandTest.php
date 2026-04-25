<?php

namespace Tests\Integration\Console;

use App\Pipeline\LaravelPipelineFactory;
use Core\Execution\JobRepository;
use Core\Job\Job;
use Core\Pipeline\Pipeline;
use Core\Pipeline\PipelineContext;
use Core\Pipeline\PipelineResult;
use Core\Pipeline\PipelineFactory;
use Core\ValueObject\JobId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase;

final class RunJobCommandTest extends TestCase
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
    }

    public function test_job_run_command_executes_job_by_id(): void
    {
        $this->app->singleton(PipelineFactory::class, function () {
            return new LaravelPipelineFactory([
                'cli' => fn () => new class implements Pipeline {
                    public function name(): string
                    {
                        return 'cli';
                    }

                    public function run(PipelineContext $context): PipelineResult
                    {
                        return PipelineResult::success();
                    }
                },
            ]);
        });

        $jobs = $this->app->make(JobRepository::class);
        $job = new Job(
            new JobId('cli-1'),
            new class implements Pipeline {
                public function name(): string
                {
                    return 'cli';
                }

                public function run(PipelineContext $context): PipelineResult
                {
                    return PipelineResult::success();
                }
            }
        );

        $jobs->save($job);

        $this->artisan('job:run', ['jobId' => 'cli-1'])
            ->expectsOutput('Job completed successfully')
            ->assertExitCode(0);
    }
}
