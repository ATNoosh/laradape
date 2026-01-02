<?php

namespace Core\Execution;

final class TimingExecutorDecorator implements ExecutorDecorator
{
    public function __construct(
        private ExecutorObserver $observer
    ) {}

    public function decorate(Executor $executor): Executor
    {
        return new ObservedExecutor($executor, $this->observer);
    }
}
