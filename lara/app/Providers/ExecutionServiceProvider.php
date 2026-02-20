<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Core\Execution\{
    Executor,
    ComposedExecutor,
    DefaultExecutor,
    JobRepository,
    RetryPolicy,
    SimpleRetryPolicy,
    TimeBox
};
use App\Execution\{
    DefaultRetryPolicy,
    LaravelRetryScheduler,
    LaravelJobExecutionStore
};
use App\Repository\LaravelJobRepository;

final class ExecutionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Base executor
        $this->app->bind(DefaultExecutor::class, function () {
            return new DefaultExecutor(
                new TimeBox(
                    startedAt: time(),
                    maxSeconds: config('jobs.timeout_seconds', 30)
                )
            );
        });

        // Retry policy
        $this->app->singleton(SimpleRetryPolicy::class, function () {
            return new SimpleRetryPolicy(
                maxAttempts: config('jobs.retry.max_attempts', 3),
                delayMs: config('jobs.retry.delay_ms', 0)
            );
        });

        $this->app->singleton(RetryPolicy::class, DefaultRetryPolicy::class);

        // Laravel integrations
        $this->app->singleton(LaravelRetryScheduler::class);
        $this->app->singleton(LaravelJobExecutionStore::class);

        // Final executor
        $this->app->bind(Executor::class, function ($app) {
            return new ComposedExecutor(
                baseExecutor: $app->make(DefaultExecutor::class),
                retryPolicy: $app->make(SimpleRetryPolicy::class),
                scheduler: $app->make(LaravelRetryScheduler::class),
                executionStore: $app->make(LaravelJobExecutionStore::class)
            );
        });
        
        $this->app->bind(
            JobRepository::class,
            LaravelJobRepository::class
        );
        
    }
}
