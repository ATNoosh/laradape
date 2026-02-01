<?php

namespace Tests\Feature;

use Tests\TestCase;
use Core\Execution\JobDispatcherInterface;
use Core\Execution\ExecutorFactory;
use Core\Job\Job;
use Core\Pipeline\Pipeline;
use Core\Pipeline\PipelineContext;
use Core\Pipeline\PipelineFactory;
use Core\Pipeline\PipelineResult;
use Core\ValueObject\JobId;

final class JobSystemContainerTest extends TestCase
{
    public function test_job_system_bindings_are_resolvable(): void
    {
        $this->assertInstanceOf(
            JobDispatcherInterface::class,
            $this->app->make(JobDispatcherInterface::class)
        );

        $this->assertInstanceOf(
            ExecutorFactory::class,
            $this->app->make(ExecutorFactory::class)
        );

        $this->assertInstanceOf(
            PipelineFactory::class,
            $this->app->make(PipelineFactory::class)
        );
    }

    public function test_job_can_be_dispatched_without_errors(): void
    {
        $dispatcher = $this->app->make(JobDispatcherInterface::class);

        $job = new Job(
            new JobId('smoke-job'),
            new class implements Pipeline {

                public function name(): string
                {
                    return 'dummy';
                }

                public function run(PipelineContext $context): PipelineResult
                {
                    return PipelineResult::success();
                }
            }
        );

        $report = $dispatcher->dispatch($job);

        $this->assertNotNull($report);
    }
}
