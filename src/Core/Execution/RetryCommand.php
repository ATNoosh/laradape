<?php

namespace Core\Execution;

final class RetryCommand
{
    public function __construct(
        private string $jobId,
        private int $attempt,
        private float $startedAt,
        private int $delayMs
    ) {}

    public function jobId(): string
    {
        return $this->jobId;
    }

    public function attempt(): int
    {
        return $this->attempt;
    }

    public function startedAt(): float
    {
        return $this->startedAt;
    }

    public function delayMs(): int
    {
        return $this->delayMs;
    }
}
