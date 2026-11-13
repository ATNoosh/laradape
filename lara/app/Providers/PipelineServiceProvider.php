<?php

namespace App\Providers;

use App\Execution\LoggingExecutorObserver;
use App\Execution\LaravelJobDispatcher;
use App\Pipeline\LaravelPipelineFactory;
use App\Pipeline\PupgraderUpgradePipeline;
use App\Pipeline\PupgraderUpgradeHopPipeline;
use Core\Execution\Executor;
use Core\Execution\ExecutorFactory;
use Core\Execution\ExecutorObserver;
use Core\Execution\JobDispatcherInterface;
use Core\Pipeline\PipelineFactory;
use Illuminate\Support\ServiceProvider;
use Pupgrader\UpgradeRunner;
use Pupgrader\Pipeline\UpgradePipelineFactory;
use Pupgrader\Reporting\ProgressReporter;
use App\Execution\DatabaseProgressReporter;

final class PipelineServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ProgressReporter::class, DatabaseProgressReporter::class);
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
                'pupgrader.upgrade.hop' => fn () => new PupgraderUpgradeHopPipeline(
                    payload: [],
                    factory: $app->make(UpgradePipelineFactory::class),
                    progressReporter: $app->make(ProgressReporter::class),
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
