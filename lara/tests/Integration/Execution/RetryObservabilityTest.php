<?php

namespace Tests\Integration\Execution;

use Core\Execution\JobDispatcherInterface;
use Core\Execution\RetryMetricsObserver;
use Core\Job\Job;
use Core\Pipeline\Pipeline;
use Core\Pipeline\PipelineContext;
use Core\Pipeline\PipelineError;
use Core\Pipeline\PipelineResult;
use Core\ValueObject\JobId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Orchestra\Testbench\TestCase;

final class RetryObservabilityTest extends TestCase
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

    public function test_retry_observer_records_scheduled(): void
    {
        Queue::fake();

        $observer = $this->app->make(RetryMetricsObserver::class);
        $before = $observer->export();

        $dispatcher = $this->app->make(JobDispatcherInterface::class);
        $job = $this->failingJob('retry-metrics-1');

        $dispatcher->dispatch($job);

        $after = $observer->export();

        $this->assertSame($before['retry_scheduled_total'] + 1, $after['retry_scheduled_total']);
    }

    public function test_retry_observer_records_aborted(): void
    {
        Queue::fake();
        $this->app['config']->set('jobs.retry.max_attempts', 1);

        $observer = $this->app->make(RetryMetricsObserver::class);
        $before = $observer->export();

        $dispatcher = $this->app->make(JobDispatcherInterface::class);
        $job = $this->failingJob('retry-metrics-2');

        $dispatcher->dispatch($job);

        $after = $observer->export();

        $this->assertSame($before['retry_aborted_total'] + 1, $after['retry_aborted_total']);
    }

    public function test_retry_observer_records_succeeded(): void
    {
        Queue::fake();

        $observer = $this->app->make(RetryMetricsObserver::class);
        $before = $observer->export();

        $dispatcher = $this->app->make(JobDispatcherInterface::class);
        $job = new Job(
            new JobId('retry-metrics-3'),
            new class implements Pipeline {
                public function name(): string
                {
                    return 'success';
                }

                public function run(PipelineContext $context): PipelineResult
                {
                    return PipelineResult::success();
                }
            }
        );

        $dispatcher->dispatch($job);

        $after = $observer->export();

        $this->assertSame($before['retry_succeeded_total'] + 1, $after['retry_succeeded_total']);
    }

    private function failingJob(string $id): Job
    {
        return new Job(
            new JobId($id),
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
    }
}
