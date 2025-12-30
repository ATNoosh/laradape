<?php

namespace Core\Job;

final class JobResult
{
    public function __construct(
        private string $state,
        private ?string $message = null
    ) {}
}
