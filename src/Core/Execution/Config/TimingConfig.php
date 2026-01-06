<?php

namespace Core\Execution\Config;

final class TimingConfig
{
    public function __construct(
        public readonly bool $enabled,
    ) {}
}

