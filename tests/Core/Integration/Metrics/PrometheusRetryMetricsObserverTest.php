<?php

namespace Tests\Infrastructure\Metrics;

use Core\Execution\PrometheusRetryMetricsObserver;
use PHPUnit\Framework\TestCase;
use Core\Execution\RetryContext;
use Core\Execution\RetryResult;
use Core\Job\Job;
use Core\Job\JobResult;
use Core\Pipeline\Pipeline;
use Core\Pipeline\PipelineContext;
use Core\Pipeline\PipelineResult;
use Core\ValueObject\JobId;

final class PrometheusRetryMetricsObserverTest extends TestCase
{
    public function test_prometheus_metrics_export(): void
    {
        $observer = new PrometheusRetryMetricsObserver();

        // Pipeline dummy
        $pipeline = new class implements Pipeline {

            public function name(): string
            {
                return 'dummy';
            }

            public function run(PipelineContext $context): PipelineResult
            {
                return PipelineResult::success();
            }
        };

        $job = new Job(new JobId('job-1'), $pipeline);

        $context = new RetryContext(
            job: $job,
            result: JobResult::failed('network error'),
            exception: null,
            attempt: 1,
            startedAt: microtime(true)
        );

        $decision = RetryResult::retry(100, 'network');

        $observer->retryScheduled($context, $decision);
        $observer->retrySucceeded($context);

        $metrics = $observer->export();

        $this->assertArrayHasKey('retry_scheduled_total', $metrics);
        $this->assertArrayHasKey('retry_succeeded_total', $metrics);
        $this->assertSame(1, $metrics['retry_scheduled_total']);
        $this->assertSame(1, $metrics['retry_succeeded_total']);
    }
}
