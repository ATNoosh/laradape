<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Core\Execution\Executor;
use Core\Execution\DefaultExecutor;
use Core\Execution\ExecutorFactory;
use App\Execution\ExecutorDecoratorBuilder;
use App\Execution\ExecutorConfigFactory;
use Core\Execution\JobDispatcher;
use Core\Execution\JobDispatcherInterface;
use Core\Pipeline\PipelineFactory;
use App\Pipeline\LaravelPipelineFactory;
use Core\Execution\ExecutorObserver;
use App\Execution\LoggingExecutorObserver;

final class JobSystemServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ExecutorFactory
        $this->app->singleton(ExecutorFactory::class, function ($app) {
            $raw = config('job-system.executor.decorators');
            $config = ExecutorConfigFactory::fromArray($raw);

            return new class(
                $app->make(ExecutorDecoratorBuilder::class),
                $config
            ) implements ExecutorFactory {

                public function __construct(
                    private ExecutorDecoratorBuilder $builder,
                    private \Core\Execution\Config\ExecutorConfig $config
                ) {}

                public function make(): Executor
                {
                    $executor = new DefaultExecutor();

                    return $this->builder->build(
                        $executor,
                        $this->config
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
            LoggingExecutorObserver::class
        );

        // JobDispatcherInterface → JobDispatcher
        $this->app->singleton(
            JobDispatcherInterface::class,
            JobDispatcher::class
        );
    }
}
