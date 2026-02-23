<?php

namespace App\Execution;

use App\Jobs\RetryJob;
use Core\Execution\JobDispatcher;
use Core\Execution\JobDispatcherInterface;
use Core\Execution\JobReport;
use Core\Execution\RetryCommand;
use Core\Execution\RetryContext;
use Core\Execution\RetryPolicy;
use Core\Job\Job;
use Illuminate\Contracts\Bus\Dispatcher;

final class LaravelJobDispatcher implements JobDispatcherInterface
{
    public function __construct(
        private JobDispatcher $inner,
        private RetryPolicy $retryPolicy,
        private Dispatcher $bus
    ) {}

    public function dispatch(Job $job, ?RetryCommand $command = null): JobReport
    {
        $attempt = $command?->attempt() ?? 1;
        $startedAt = $command?->startedAt() ?? microtime(true);

        $report = $this->inner->dispatch($job);

        if ($report->isSkipped()) {
            return $report;
        }

        if (! $report->isFailed()) {
            return $report;
        }

        $context = new RetryContext(
            job: $job,
            result: $report->result(),
            exception: null,
            attempt: $attempt,
            startedAt: $startedAt
        );

        $decision = $this->retryPolicy->decide($context);

        if (! $decision->shouldRetry()) {
            return $report;
        }

        $retry = new RetryJob(
            new RetryCommand(
                jobId: $job->id()->value(),
                attempt: $attempt + 1,
                startedAt: $startedAt,
                delayMs: $decision->delayMs() ?? 0
            )
        );

        $delaySeconds = (int) ceil(($decision->delayMs() ?? 0) / 1000);
        if ($delaySeconds > 0) {
            $retry->delay($delaySeconds);
        }

        $this->bus->dispatch($retry);

        return $report;
    }
}
