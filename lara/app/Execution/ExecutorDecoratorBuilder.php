<?php

namespace App\Execution;

use Illuminate\Contracts\Container\Container;
use Core\Execution\Executor;
use Core\Execution\TimingExecutorDecorator;
use Core\Execution\RetryExecutorDecorator;
use Core\Execution\CircuitBreakerExecutorDecorator;
use Core\Execution\CircuitBreaker;
use Core\Execution\ExecutorObserver;
use Core\Execution\MaxAttemptsRetryPolicy;
use Core\Execution\Config\ExecutorConfig;

final class ExecutorDecoratorBuilder
{
    public function __construct(
        private Container $container
    ) {}

    public function build(Executor $executor, ExecutorConfig $config): Executor
    {
        if ($config->timing->enabled) {
            $timingDecorator = new TimingExecutorDecorator(
                $this->container->make(ExecutorObserver::class)
            );
            $executor = $timingDecorator->decorate($executor);
        }

        if ($config->retry->enabled) {
            $retryPolicy = new MaxAttemptsRetryPolicy(
                $config->retry->maxAttempts
            );
            $executor = new RetryExecutorDecorator(
                $executor,
                $retryPolicy
            );
        }

        if ($config->circuitBreaker->enabled) {
            $breaker = new CircuitBreaker(
                $config->circuitBreaker->failureThreshold,
                $config->circuitBreaker->cooldownSeconds
            );

            $executor = new CircuitBreakerExecutorDecorator(
                $executor,
                $breaker
            );
        }

        return $executor;
    }
}
