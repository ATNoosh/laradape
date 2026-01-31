<?php

namespace Core\Execution;

interface RetryScheduler
{
    public function schedule(RetryCommand $command): void;
}
