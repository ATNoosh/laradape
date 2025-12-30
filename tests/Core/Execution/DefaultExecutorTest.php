<?php

use PHPUnit\Framework\TestCase;
use Core\Execution\DefaultExecutor;
use Core\Execution\TimeBox;
use Core\Pipeline\DefaultPipeline;
use Core\Pipeline\PipelineContext;
use Core\Pipeline\PipelineStep;
use Core\Job\Job;
use Core\ValueObject\JobId;
use Core\Job\JobState;
use Core\Pipeline\PipelineResult;

final class DefaultExecutorTest extends TestCase
{
    public function test_job_completes_successfully(): void
    {
        $pipeline = new DefaultPipeline([
            new class implements PipelineStep {
                public function execute(PipelineContext $context): PipelineResult {
                    $context->set('done', true);
                    return new PipelineResult();
                }
            }
        ]);

        $job = new Job(new JobId('job-1'), $pipeline);

        $executor = new DefaultExecutor(
            new TimeBox(time(), 60)
        );

        $report = $executor->execute($job);

        $this->assertSame(
            JobState::COMPLETED,
            $report->result()->state()
        );
    }

    public function test_job_times_out_before_execution(): void
    {
        $pipeline = new DefaultPipeline([]);

        $job = new Job(new JobId('job-2'), $pipeline);

        $executor = new DefaultExecutor(
            new TimeBox(time() - 100, 1)
        );

        $report = $executor->execute($job);

        $this->assertSame(
            JobState::TIMED_OUT,
            $report->result()->state()
        );
    }
}
