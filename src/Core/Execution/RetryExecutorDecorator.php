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
        private RetryScheduler $scheduler,
        private ?RetryObserver $observer = null
    ) {}

    public function execute(Job $job, ?RetryCommand $command = null): JobReport
    {
        $attempt   = $command?->attempt() ?? 1;
        $startedAt = $command?->startedAt() ?? microtime(true);

        try {
            $report = $this->inner->execute($job);

            if ($report->result()->isCompleted()) {
                $this->observer?->retrySucceeded(
                    new RetryContext($job, $report->result(), null, $attempt, $startedAt)
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
        } catch (Throwable $e) {

            $report = JobReport::failed(
                $job->id(),
                $e->getMessage()
            );

            $context = new RetryContext(
                job: $job,
                result: $report->result(),
                exception: $e,
                attempt: $attempt,
                startedAt: $startedAt
            );
        }

        $decision = $this->policy->decide($context);

        if (! $decision->shouldRetry()) {
            $this->observer?->retryAborted($context, $decision);
            return $report;
        }

        $this->observer?->retryScheduled($context, $decision);

        $this->scheduler->schedule(
            new RetryCommand(
                jobId: $job->id()->value(),
                attempt: $attempt + 1,
                startedAt: $startedAt,
                delayMs: $decision->delayMs() ?? 0
            )
        );

        return $report;
    }
}
