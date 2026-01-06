<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Core\Execution\Executor;
use Core\Execution\DefaultExecutor;
use Core\Execution\ExecutorFactory;
use App\Execution\ExecutorDecoratorBuilder;

final class JobSystemServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ExecutorFactory::class, function ($app) {

            return new class(
                $app->make(ExecutorDecoratorBuilder::class),
                config('job-system.executor.decorators')
            ) implements ExecutorFactory {

                public function __construct(
                    private ExecutorDecoratorBuilder $builder,
                    private array $decoratorConfig
                ) {}

                public function create(): Executor
                {
                    $executor = new DefaultExecutor();

                    return $this->builder->build(
                        $executor,
                        $this->decoratorConfig
                    );
                }
            };
        });
    }
}
