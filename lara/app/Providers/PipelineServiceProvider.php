<?php

namespace App\Providers;

use App\Execution\LoggingExecutorObserver;
use App\Pipeline\LaravelPipelineFactory;
use Core\Execution\Executor;
use Core\Execution\ExecutorFactory;
use Core\Execution\ExecutorObserver;
use Core\Execution\JobDispatcher;
use Core\Execution\JobDispatcherInterface;
use Core\Pipeline\PipelineFactory;
use Illuminate\Support\ServiceProvider;

final class PipelineServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        /*
         |------------------------------------------------------------
         | Job Dispatcher
         |------------------------------------------------------------
         */
        $this->app->singleton(
            JobDispatcherInterface::class,
            JobDispatcher::class
        );

        /*
         |------------------------------------------------------------
         | Executor Observer
         |------------------------------------------------------------
         */
        $this->app->singleton(
            ExecutorObserver::class,
            LoggingExecutorObserver::class
        );

        /*
         |------------------------------------------------------------
         | Pipeline Factory
         |------------------------------------------------------------
         */
        $this->app->singleton(PipelineFactory::class, function () {
            // فعلاً empty registry – تست‌ها pipeline خودشون رو میدن
            return new LaravelPipelineFactory([]);
        });

        $this->app->singleton(ExecutorFactory::class, function ($app) {
            return new class ($app) implements ExecutorFactory {
        
                public function __construct(
                    private $app
                ) {}
        
                public function make(): Executor
                {
                    return $this->app->make(Executor::class);
                }
            };
        });
        
    }

}
