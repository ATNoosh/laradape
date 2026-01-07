<?php

namespace Core\Execution;

use Core\Job\Job;
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
                $retry = $this->policy->decide(
                    $job,
                    $report->result(),
                    null,
                    $attempt
                );
            } catch (Throwable $e) {
                $retry = $this->policy->decide(
                    $job,
                    \Core\Job\JobResult::failed($e->getMessage()),
                    $e,
                    $attempt
                );

                if (! $retry->shouldRetry()) {
                    throw $e;
                }
            }

            if (! $retry->shouldRetry()) {
                return $report;
            }

            if ($retry->delayMs() > 0) {
                usleep($retry->delayMs() * 1000);
            }

            $attempt++;
        }
    }
}
