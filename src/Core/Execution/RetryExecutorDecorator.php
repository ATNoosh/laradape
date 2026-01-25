<?php

namespace Core\Execution;

use Core\Job\Job;
use Core\Job\JobResult;
use Throwable;

final class RetryExecutorDecorator implements Executor
{
    public function __construct(
        private Executor $inner,
        private RetryPolicy $policy,
        private ?RetryObserver $observer = null,
        private ?RetryMetricsObserver $metrics = null
    ) {}

    public function execute(Job $job): JobReport
    {
        $attempt = 1;
        $startedAt = microtime(true);

        while (true) {
            try {
                $report = $this->inner->execute($job);

                if ($report->result()->isCompleted()) {
                    $this->metrics?->retrySucceeded(
                        new RetryContext(
                            job: $job,
                            result: $report->result(),
                            exception: null,
                            attempt: $attempt,
                            startedAt: $startedAt
                        )
                    );

                    return $report;
                }

                $context = new RetryContext(
                    job: $job,
                    result: $report->result(),
                    exception: null,
                    attempt: $attempt,
                    startedAt: $startedAt
                );

                $decision = $this->policy->decide($context);
            } catch (Throwable $e) {
                $context = new RetryContext(
                    job: $job,
                    result: JobResult::failed($e->getMessage()),
                    exception: $e,
                    attempt: $attempt,
                    startedAt: $startedAt
                );

                $decision = $this->policy->decide($context);
            }

            if (! $decision->shouldRetry()) {
                $this->observer?->retryAborted($context, $decision);
                $this->metrics?->retryAborted($context, $decision);

                return $report ?? JobReport::failed(
                    $job->id(),
                    $decision->reason()
                );
            }

            $this->observer?->retryScheduled(
                $context,
                $decision
            );
            $this->metrics?->retryScheduled($context, $decision);

            if ($decision->delayMs() !== null && $decision->delayMs() > 0) {
                usleep($decision->delayMs() * 1000);
            }

            $attempt++;
        }
    }
}
