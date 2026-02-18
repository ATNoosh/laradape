<?php

namespace App\Execution;

use App\Jobs\RetryJob;
use Core\Execution\JobDispatcherInterface;
use Core\Execution\JobReport;
use Core\Execution\RetryContext;
use Core\Execution\RetryPolicy;
use Core\Job\Job;
use Illuminate\Support\Facades\Queue;

final class LaravelJobDispatcher implements JobDispatcherInterface
{
    private RetryPolicy $retryPolicy;

    public function __construct(RetryPolicy $retryPolicy)
    {
        $this->retryPolicy = $retryPolicy;
    }

    public function dispatch(Job $job): JobReport
    {
        try {
            $job->pipeline()->run($job->context());
        } catch (\Throwable $e) {
            $context = new RetryContext($job, $job->attempt(), $e);
            $result = $this->retryPolicy->decide($context);

            if ($result->shouldRetry) {
                Queue::later($result->delaySeconds, RetryJob::class, ['job' => $job]);
            }
        }
    }
}
