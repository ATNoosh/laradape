<?php

use PHPUnit\Framework\TestCase;
use Core\Execution\DefaultExecutor;
use Core\Execution\TimeBox;
use Core\Pipeline\DefaultPipeline;
use Core\Job\Job;
use Core\ValueObject\JobId;
use Core\Job\JobState;

final class ExecutorDecisionMatrixTest extends TestCase
{
    public function test_executor_prioritizes_timebox_over_pipeline(): void
    {
        $executor = new DefaultExecutor(
            new TimeBox(time() - 100, 1)
        );

        $job = new Job(
            new JobId('job-x'),
            new DefaultPipeline([new SuccessfulStep()])
        );

        $report = $executor->execute($job);

        $this->assertSame(
            JobState::TIMED_OUT,
            $report->result()->state()
        );
    }
}
