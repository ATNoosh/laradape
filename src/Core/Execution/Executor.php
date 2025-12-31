<?php

namespace Core\Execution;

use Core\Job\Job;

interface Executor
{
    public function execute(Job $job): JobReport;
}
