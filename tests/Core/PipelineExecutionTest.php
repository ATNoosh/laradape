<?php

use PHPUnit\Framework\TestCase;
use Core\Pipeline\DefaultPipeline;
use Core\Pipeline\PipelineContext;
use Core\Execution\DefaultExecutor;
use Core\Execution\TimeBox;
use Core\Job\Job;
use Core\Pipeline\PipelineResult;
use Core\ValueObject\JobId;
use Core\Pipeline\PipelineStep;

final class PipelineExecutionTest extends TestCase
{
    public function test_pipeline_runs_successfully(): void
    {
        $pipeline = new DefaultPipeline([
            new class implements PipelineStep {
                public function execute(PipelineContext $context): PipelineResult {
                    $context->set('x', 42);
                    return new PipelineResult();
                }
            },
            new class implements PipelineStep {
                public function execute(PipelineContext $context): PipelineResult  {
                    $this->assertSame(42, $context->get('x'));
                    return new PipelineResult();
                }
            },
        ]);

        $job = new Job(new JobId('job-1'), $pipeline);

        $executor = new DefaultExecutor(
            new TimeBox(time(), 60)
        );

        $report = $executor->execute($job);

        $this->assertNotNull($report->result());
    }
}
