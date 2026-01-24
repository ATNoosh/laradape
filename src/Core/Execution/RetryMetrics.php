<?php

namespace Core\Execution;

final class RetryMetrics
{
    public function __construct(
        public readonly int $attempt,
        public readonly ?int $delayMs,
        public readonly string $reason
    ) {}
}
