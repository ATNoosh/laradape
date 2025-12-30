<?php

namespace Core\Execution;

final class TimeBox
{
    public function __construct(
        private int $startedAt,
        private int $maxSeconds
    ) {}

    public function isExpired(): bool
    {
        return (time() - $this->startedAt) >= $this->maxSeconds;
    }
}
