<?php

namespace App\Execution;

use Core\Execution\TimeBox;
use Illuminate\Contracts\Config\Repository;

final class TimeBoxFactory
{
    public function __construct(
        private Repository $config
    ) {}

    public function make(?int $startedAt = null): TimeBox
    {
        return new TimeBox(
            startedAt: $startedAt ?? time(),
            maxSeconds: (int) $this->config->get('jobs.timeout_seconds', 30)
        );
    }
}
