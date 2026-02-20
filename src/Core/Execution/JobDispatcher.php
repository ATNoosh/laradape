<?php

namespace Core\Execution;

use Core\Job\Job;
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

        return $executor->execute($job);
    }
}
