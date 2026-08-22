<?php

namespace App\Providers;

use App\Execution\LoggingExecutorObserver;
use App\Execution\LaravelJobDispatcher;
use App\Pipeline\LaravelPipelineFactory;
use App\Pipeline\PupgraderUpgradePipeline;
use Core\Execution\Executor;
use Core\Execution\ExecutorFactory;
use Core\Execution\ExecutorObserver;
use Core\Execution\JobDispatcherInterface;
use Core\Pipeline\PipelineFactory;
use Illuminate\Support\ServiceProvider;
use Pupgrader\UpgradeRunner;

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
            LaravelJobDispatcher::class
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
        $this->app->singleton(PipelineFactory::class, function ($app) {
            return new LaravelPipelineFactory([
                'pupgrader.upgrade' => fn () => new PupgraderUpgradePipeline(
                    payload: [],
                    runner: $app->make(UpgradeRunner::class)
                ),
            ]);
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
