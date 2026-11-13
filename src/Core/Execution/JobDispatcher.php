<?php

namespace Core\Execution;

use Core\Job\Job;
use Core\Job\JobStatus;
use Core\Pipeline\PipelineFactory;

final class JobDispatcher implements JobDispatcherInterface
{
    public function __construct(
        private ExecutorFactory $executorFactory,
        private PipelineFactory $pipelineFactory,
        private JobRepository $jobs

    ) {}

    public function dispatch(Job $job, ?RetryCommand $command = null): JobReport
    {
        $this->jobs->save($job);

        $executor = $this->executorFactory->make();

        try {
            $report = $executor->execute($job);
        } catch (\Throwable $exception) {
            $this->jobs->updateStatus($job->id(), JobStatus::Failed, $exception->getMessage());
            throw $exception;
        }

        if (! $report->isSkipped()) {
            $this->jobs->updateStatus(
                $job->id(),
                $report->isSuccessful() ? JobStatus::Completed : JobStatus::Failed,
                $report->result()?->message()
            );
        }

        return $report;
    }
}
