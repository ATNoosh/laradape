<?php

namespace Core\ValueObject;

final class Duration
{
    public function __construct(private int $seconds)
    {
        if ($seconds < 0) {
            throw new \InvalidArgumentException('Duration must be >= 0');
        }
    }

    public function seconds(): int
    {
        return $this->seconds;
    }
}
