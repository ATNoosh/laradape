<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Core\Execution\Executor;
use Core\Execution\DefaultExecutor;
use Core\Execution\ExecutorFactory;
use App\Execution\ExecutorDecoratorBuilder;
use Core\Execution\JobDispatcher;
use Core\Execution\JobDispatcherInterface;
use Core\Pipeline\PipelineFactory;
use App\Pipeline\LaravelPipelineFactory;
use Core\Execution\ExecutorObserver;
use App\Execution\LaravelExecutorObserver;

final class JobSystemServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ExecutorFactory
        $this->app->singleton(ExecutorFactory::class, function ($app) {
            return new class(
                $app->make(ExecutorDecoratorBuilder::class),
                config('job-system.executor.decorators')
            ) implements ExecutorFactory {

                public function __construct(
                    private ExecutorDecoratorBuilder $builder,
                    private array $decoratorConfig
                ) {}

                public function make(): Executor
                {
                    $executor = new DefaultExecutor();

                    return $this->builder->build(
                        $executor,
                        $this->decoratorConfig
                    );
                }
            };
        });

        // PipelineFactory
        $this->app->singleton(
            PipelineFactory::class,
            LaravelPipelineFactory::class
        );

        // ExecutorObserver
        $this->app->singleton(
            ExecutorObserver::class,
            LaravelExecutorObserver::class
        );

        // JobDispatcherInterface → JobDispatcher
        $this->app->singleton(
            JobDispatcherInterface::class,
            JobDispatcher::class
        );
    }
}
