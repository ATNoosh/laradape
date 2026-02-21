<?php

namespace Tests\Integration\Execution;

use App\Jobs\RetryJob;
use App\Pipeline\LaravelPipelineFactory;
use Core\Execution\JobDispatcherInterface;
use Core\Execution\JobRepository;
use Core\Execution\RetryCommand;
use Core\Job\Job;
use Core\Pipeline\Pipeline;
use Core\Pipeline\PipelineContext;
use Core\Pipeline\PipelineError;
use Core\Pipeline\PipelineFactory;
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

    public function test_retry_job_receives_delay_from_policy(): void
    {
        // Arrange
        Queue::fake();
        $this->app['config']->set('jobs.retry.delay_ms', 1500);

        $dispatcher = $this->app->make(JobDispatcherInterface::class);

        $job = new Job(
            new JobId('retry-delay-1'),
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

        Queue::assertPushed(RetryJob::class, function (RetryJob $queued) {
            return $queued->delay === 2;
        });
    }

    public function test_retry_is_not_scheduled_when_policy_stops(): void
    {
        // Arrange
        Queue::fake();
        $this->app['config']->set('jobs.retry.max_attempts', 1);

        $dispatcher = $this->app->make(JobDispatcherInterface::class);

        $job = new Job(
            new JobId('retry-stop-1'),
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

        Queue::assertNothingPushed();
    }

    public function test_retry_job_executes_pipeline_via_repository_and_dispatcher(): void
    {
        // Arrange
        Queue::fake();

        $this->app->singleton(PipelineFactory::class, function () {
            return new LaravelPipelineFactory([
                'retryable' => fn () => new class implements Pipeline {
                    public function name(): string
                    {
                        return 'retryable';
                    }

                    public function run(PipelineContext $context): PipelineResult
                    {
                        return PipelineResult::failure(
                            new PipelineError('failed', 'boom')
                        );
                    }
                },
            ]);
        });

        $jobs = $this->app->make(JobRepository::class);
        $dispatcher = $this->app->make(JobDispatcherInterface::class);

        $job = new Job(
            new JobId('retry-exec-1'),
            new class implements Pipeline {
                public function name(): string
                {
                    return 'retryable';
                }

                public function run(PipelineContext $context): PipelineResult
                {
                    return PipelineResult::failure(
                        new PipelineError('failed', 'boom')
                    );
                }
            }
        );

        $jobs->save($job);

        $retryJob = new RetryJob(
            new RetryCommand(
                jobId: $job->id()->value(),
                attempt: 2,
                startedAt: microtime(true),
                delayMs: 0
            )
        );

        // Act
        $retryJob->handle($dispatcher, $jobs);

        // Assert
        Queue::assertPushed(RetryJob::class, function (RetryJob $queued) use ($job) {
            return $queued->command->jobId() === $job->id()->value()
                && $queued->command->attempt() === 3;
        });
    }
}
