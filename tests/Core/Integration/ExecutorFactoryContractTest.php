<?php

use Core\Job\JobResult;
use PHPUnit\Framework\TestCase;
use Core\Execution\Executor;
use Core\Execution\ExecutorFactory;
use Core\Job\Job;
use Core\Execution\JobReport;

final class ExecutorFactoryContractTest extends TestCase
{
    public function test_executor_factory_creates_executor(): void
    {
        $factory = new class implements ExecutorFactory {
            public function make(): Executor
            {
                return new class implements Executor {
                    public function execute(Job $job): JobReport
                    {
                        return JobReport::completed($job->id());
                    }
                };
            }
        };

        $executor = $factory->make();

        $this->assertInstanceOf(Executor::class, $executor);
    }
}
