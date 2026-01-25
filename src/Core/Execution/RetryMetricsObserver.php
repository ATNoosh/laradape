<?php

namespace Core\Execution;

interface RetryMetricsObserver extends RetryObserver
{
    public function export(): array;
}
