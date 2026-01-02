<?php

use Core\Execution\DefaultExecutor;
use Core\Execution\Executor;
use Core\Execution\ExecutorDecorator;
use Core\Execution\TimeBox;
use Core\Job\Job;
use Core\Pipeline\DefaultPipeline;
use Core\Pipeline\PipelineContext;
use Core\Pipeline\PipelineResult;
use Core\Pipeline\PipelineStep;
use Core\ValueObject\JobId;
use PHPUnit\Framework\TestCase;

final class NoOpExecutorDecorator implements ExecutorDecorator
{
    public function decorate(Executor $executor): Executor
    {
        return $executor;
    }
}

final class ExecutorDecoratorContractTest extends TestCase
{
    public function test_executor_decorator_preserves_job_result(): void
    {
                // arrange
        $pipeline = new DefaultPipeline([
            new class implements PipelineStep {
                public function execute(PipelineContext $context): PipelineResult {
                    return PipelineResult::success();
                }
            }
        ]);

        $job = new Job(new JobId('job-1'), $pipeline);

        $executor = new DefaultExecutor(
            new TimeBox(time(), 60)
        );

        $decorator = new NoOpExecutorDecorator();

        // act
        $originalReport = $executor->execute($job);
        $decoratedExecutor = $decorator->decorate($executor);
        $decoratedReport = $decoratedExecutor->execute($job);

        // assert
        $this->assertSame(
            $originalReport->result()->state(),
            $decoratedReport->result()->state()
        );

        $this->assertSame(
            $originalReport->result()->message(),
            $decoratedReport->result()->message()
        );
    }
}
