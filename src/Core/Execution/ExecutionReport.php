<?php

namespace Core\Execution;

use Core\Job\JobResult;

final class ExecutionReport
{
    public function __construct(
        private JobResult $result
    ) {}

    public function result(): JobResult
    {
        return $this->result;
    }
}
