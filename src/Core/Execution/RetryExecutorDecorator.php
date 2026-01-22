<?php

namespace Core\Execution;

use Core\Job\Job;
use Core\Job\JobResult;
use Throwable;

final class RetryExecutorDecorator implements Executor
{
    public function __construct(
        private Executor $inner,
        private RetryPolicy $policy
    ) {}

    public function execute(Job $job): JobReport
    {
        $attempt = 1;

        while (true) {
            try {
                $report = $this->inner->execute($job);

                if ($report->result()->isCompleted()) {
                    return $report;
                }

                $decision = $this->policy->decide(
                    $job,
                    $report->result(),
                    null,
                    $attempt
                );

            } catch (Throwable $e) {

                $decision = $this->policy->decide(
                    $job,
                    JobResult::failed($e->getMessage()),
                    $e,
                    $attempt
                );
            }

            if (! $decision->shouldRetry()) {
                return $report ?? JobReport::failed(
                    $job->id(),
                    $decision->reason()
                );
            }

            if ($decision->delayMs() !== null && $decision->delayMs() > 0) {
                usleep($decision->delayMs() * 1000);
            }

            $attempt++;
        }

    }
}
