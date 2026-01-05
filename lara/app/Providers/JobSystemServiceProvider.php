<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Core\Execution\Executor;
use Core\Execution\DefaultExecutor;
use Core\Execution\ExecutorFactory;
use Core\Execution\JobDispatcher;
use Core\Execution\JobDispatcherInterface;
use Core\Pipeline\PipelineFactory;
use Core\Pipeline\DefaultPipeline;
use Core\Pipeline\Pipeline;

final class JobSystemServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Pipeline Factory
        $this->app->singleton(PipelineFactory::class, function () {
            return new class implements PipelineFactory {
                public function make(string $name): Pipeline
                {
                    return new DefaultPipeline([]);
                }
            };
        });

        // Executor Factory
        $this->app->singleton(ExecutorFactory::class, function ($app) {
            return new class($app) implements ExecutorFactory {
                public function __construct(private $app) {}

                public function make(): Executor
                {
                    return $this->app->make(DefaultExecutor::class);
                }
            };
        });

        // Job Dispatcher
        $this->app->singleton(JobDispatcherInterface::class, function ($app) {
            return new JobDispatcher(
                $app->make(ExecutorFactory::class),
                $app->make(PipelineFactory::class)
            );
        });
    }
}
