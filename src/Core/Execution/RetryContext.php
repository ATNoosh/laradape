<?php

namespace Core\Execution;

use Core\Job\Job;
use Core\Job\JobResult;
use Throwable;

final class RetryContext
{
    public function __construct(
        public readonly Job $job,
        public readonly JobResult $result,
        public readonly ?Throwable $exception,
        public readonly int $attempt,
        public readonly float $startedAt
    ) {}

    public function isException(): bool
    {
        return $this->exception !== null;
    }

    public function isFailure(): bool
    {
        return $this->result->isFailed();
    }
}
