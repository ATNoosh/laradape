<?php

namespace Core\Execution;

use Core\Job\JobResult;
use Core\ValueObject\JobId;

final class JobReport
{
    private function __construct(
        private JobId $jobId,
        private JobResult $result
    ) {}

    public static function completed(JobId $jobId): self
    {
        return new self(
            $jobId,
            JobResult::completed()
        );
    }

    public static function failed(JobId $jobId, string $reason): self
    {
        return new self(
            $jobId,
            JobResult::failed($reason)
        );
    }

    public static function timedOut(JobId $jobId, ?string $reason = null): self
    {
        return new self(
            $jobId,
            JobResult::timedOut($reason ?? 'Execution time exceeded')
        );
    }

    public function jobId(): JobId
    {
        return $this->jobId;
    }

    public function result(): JobResult
    {
        return $this->result;
    }

    /* Optional sugar */
    public function isSuccessful(): bool
    {
        return $this->result->isSuccessful();
    }

    public function isFailed(): bool
    {
        return $this->result->isFailed();
    }

    public function isTimedOut(): bool
    {
        return $this->result->isTimedOut();
    }
}
