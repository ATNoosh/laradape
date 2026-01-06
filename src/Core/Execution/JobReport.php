<?php

namespace Core\Execution;

use Core\Job\JobResult;
use Core\ValueObject\JobId;

final class JobReport
{
    public function __construct(
        private JobId $jobId,
        private JobResult $result
    ) {}

    public function jobId(): JobId
    {
        return $this->jobId;
    }

    public function result(): JobResult
    {
        return $this->result;
    }
}
