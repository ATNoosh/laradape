<?php

namespace Core\Execution;

interface ExecutorDecorator
{
    public function decorate(Executor $executor): Executor;
}
