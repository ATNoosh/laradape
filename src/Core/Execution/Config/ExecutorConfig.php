<?php

namespace Core\Execution\Config;

final class ExecutorConfig
{
    public function __construct(
        public readonly TimingConfig $timing,
        public readonly RetryConfig $retry,
        public readonly CircuitBreakerConfig $circuitBreaker,
    ) {}
}

