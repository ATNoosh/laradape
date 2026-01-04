<?php

use Core\Execution\JobDispatcher;
use Core\Job\JobResult;
use PHPUnit\Framework\TestCase;
use Core\Job\Job;
use Core\Execution\JobReport;

final class JobDispatcherContractTest extends TestCase
{
    public function test_job_dispatcher_contract_is_callable(): void
    {
        $dispatcher = new class implements JobDispatcher {
            public function dispatch(Job $job): JobReport
            {
                return new JobReport(new JobResult(''));
            }
        };

        $this->assertInstanceOf(JobDispatcher::class, $dispatcher);
    }
}
