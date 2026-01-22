<?php

namespace Core\Execution;

final class RetryResult
{
    private function __construct(
        private bool $shouldRetry,
        private string $reason,
        private ?int $delayMs = null
    ) {}

    public static function retry(int $delayMs = 0, string $reason = ''): self
    {
        return new self(true, $reason, $delayMs );
    }

    public static function doNotRetry(string $reason): self
    {
        return new self(false, $reason);
    }

    public function shouldRetry(): bool
    {
        return $this->shouldRetry;
    }

    public function delayMs(): ?int
    {
        return $this->delayMs;
    }

    public function reason(): string 
    {
        return $this->reason;
    }
}

