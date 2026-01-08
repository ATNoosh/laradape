<?php

use PHPUnit\Framework\TestCase;
use Core\Execution\MetricsCollector;
use Core\Execution\MetricsExecutorObserver;
use Core\Execution\JobReport;
use Core\Job\Job;
use Core\Job\JobResult;
use Core\Job\JobState;
use Core\Pipeline\DefaultPipeline;
use Core\Pipeline\Pipeline;
use Core\Pipeline\PipelineContext;
use Core\Pipeline\PipelineResult;
use Core\ValueObject\JobId;

final class FakeMetricsCollector implements MetricsCollector
{
    /** @var array<array{string, array}> */
    public array $increments = [];

    /** @var array<array{string, float, array}> */
    public array $observations = [];

    public function increment(string $metric, array $labels = []): void
    {
        $this->increments[] = [$metric, $labels];
    }

    public function observe(string $metric, float $value, array $labels = []): void
    {
        $this->observations[] = [$metric, $value, $labels];
    }
}

final class MetricsObserverTest extends TestCase
{
    private function dummyPipeline(): Pipeline
    {
        return new DefaultPipeline([
            new class implements \Core\Pipeline\PipelineStep {
                public function execute(PipelineContext $context): PipelineResult
                {
                    return PipelineResult::success();
                }
            }
        ]);
    }

    public function test_metrics_are_recorded_on_job_start(): void
    {
        $metrics = new FakeMetricsCollector();
        $observer = new MetricsExecutorObserver($metrics);

        $observer->jobStarted(
            new Job(new JobId('job-1'), $this->dummyPipeline())
        );

        $this->assertSame(
            [['jobs_started_total', []]],
            $metrics->increments
        );
    }

    public function test_metrics_are_recorded_on_job_finish(): void
    {
        $metrics = new FakeMetricsCollector();
        $observer = new MetricsExecutorObserver($metrics);

        $job = new Job(new JobId('job-2'), $this->dummyPipeline());

        $report = JobReport::completed($job->id());

        $observer->jobFinished(
            $job,
            $report,
            12.5
        );

        $this->assertCount(1, $metrics->increments);
        $this->assertCount(1, $metrics->observations);

        $this->assertSame('jobs_finished_total', $metrics->increments[0][0]);
        $this->assertSame(['state' => JobState::COMPLETED], $metrics->increments[0][1]);

        $this->assertSame('job_duration_ms', $metrics->observations[0][0]);
        $this->assertSame(12.5, $metrics->observations[0][1]);
        $this->assertSame(['state' => JobState::COMPLETED], $metrics->observations[0][2]);
    }

    public function test_metrics_are_recorded_on_job_failure(): void
    {
        $metrics = new FakeMetricsCollector();
        $observer = new MetricsExecutorObserver($metrics);

        $job = new Job(new JobId('job-3'), $this->dummyPipeline());

        $observer->jobFailed($job, new \RuntimeException(), 20.1);

        $this->assertNotEmpty($metrics->increments);
        $this->assertSame('jobs_failed_total', $metrics->increments[0][0]);

        $this->assertNotEmpty($metrics->observations);
        $this->assertSame('job_duration_ms', $metrics->observations[0][0]);
        $this->assertSame(['state' => 'failed'], $metrics->observations[0][2]);
    }
}


