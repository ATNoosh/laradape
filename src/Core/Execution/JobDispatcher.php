<?php

namespace Core\Execution;

use Core\Job\Job;
use Core\Pipeline\PipelineFactory;

final class JobDispatcher implements JobDispatcherInterface
{
    public function __construct(
        private ExecutorFactory $executorFactory,
        private PipelineFactory $pipelineFactory
    ) {}

    public function dispatch(Job $job): JobReport
    {
        $executor = $this->executorFactory->make();

        return $executor->execute($job);
    }
}
