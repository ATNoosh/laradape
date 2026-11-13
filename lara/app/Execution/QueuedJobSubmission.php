<?php

namespace App\Execution;

use App\Jobs\ExecuteQueuedJob;
use Core\Execution\JobRepository;
use Core\Job\Job;
use Core\ValueObject\JobId;
use Illuminate\Contracts\Bus\Dispatcher;

final class QueuedJobSubmission
{
    public function __construct(
        private JobRepository $jobs,
        private Dispatcher $bus
    ) {}

    public function submit(Job $job): JobId
    {
        $this->jobs->save($job);
        $this->bus->dispatch((new ExecuteQueuedJob($job->id()->value()))->onQueue($job->queue()));

        return $job->id();
    }

    public function submitIfMissing(Job $job): JobId
    {
        if ($this->jobs->saveIfMissing($job)) {
            $this->bus->dispatch((new ExecuteQueuedJob($job->id()->value()))->onQueue($job->queue()));
        }

        return $job->id();
    }
}
