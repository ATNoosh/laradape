<?php

namespace Core\Execution\Config;

final class RetryConfig
{
    public function __construct(
        public readonly bool $enabled,
        public readonly int $maxAttempts,
    ) {}
}

