<?php

namespace Core\Execution;

final class RetryResult
{
    private function __construct(
        private bool $shouldRetry,
        private ?int $delayMs = null
    ) {}

    public static function retry(int $delayMs = 0): self
    {
        return new self(true, $delayMs);
    }

    public static function doNotRetry(): self
    {
        return new self(false);
    }

    public function shouldRetry(): bool
    {
        return $this->shouldRetry;
    }

    public function delayMs(): ?int
    {
        return $this->delayMs;
    }
}

