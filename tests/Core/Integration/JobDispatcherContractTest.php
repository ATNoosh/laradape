<?php

use Core\Execution\JobDispatcherInterface;
use Core\Job\JobResult;
use PHPUnit\Framework\TestCase;
use Core\Job\Job;
use Core\Execution\JobReport;

final class JobDispatcherContractTest extends TestCase
{
    public function test_job_dispatcher_contract_is_callable(): void
    {
        $dispatcher = new class implements JobDispatcherInterface {
            public function dispatch(Job $job): JobReport
            {
                return JobReport::completed($job->id());
            }
        };

        $this->assertInstanceOf(JobDispatcherInterface::class, $dispatcher);
    }
}
