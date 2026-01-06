<?php

namespace Core\Execution\Config;

final class CircuitBreakerConfig
{
    public function __construct(
        public readonly bool $enabled,
        public readonly int $failureThreshold,
        public readonly int $cooldownSeconds,
    ) {}
}

