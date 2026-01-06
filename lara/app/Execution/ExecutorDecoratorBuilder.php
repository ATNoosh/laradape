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

final class ExecutorDecoratorBuilder
{
    public function __construct(
        private Container $container
    ) {}

    public function build(Executor $executor, array $config): Executor
    {
        if ($config['timing']['enabled'] ?? false) {
            $timingDecorator = new TimingExecutorDecorator(
                $this->container->make(ExecutorObserver::class)
            );
            $executor = $timingDecorator->decorate($executor);
        }

        if ($config['retry']['enabled'] ?? false) {
            $retryPolicy = new MaxAttemptsRetryPolicy(
                $config['retry']['max_attempts'] ?? 1
            );
            $executor = new RetryExecutorDecorator(
                $executor,
                $retryPolicy
            );
        }

        if ($config['circuit_breaker']['enabled'] ?? false) {
            $breaker = new CircuitBreaker(
                $config['circuit_breaker']['failure_threshold'],
                $config['circuit_breaker']['cooldown_seconds']
            );

            $executor = new CircuitBreakerExecutorDecorator(
                $executor,
                $breaker
            );
        }

        return $executor;
    }
}
