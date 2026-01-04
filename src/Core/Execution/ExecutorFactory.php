<?php

namespace Core\Execution;

interface ExecutorFactory {
    public function make(): Executor;
}