<?php

namespace Core\Execution;

use Core\Job\Job;

interface JobDispatcher {
    public function dispatch(Job $job): JobReport;
}