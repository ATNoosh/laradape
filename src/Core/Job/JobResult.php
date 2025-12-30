<?php

namespace Core\Job;

final class JobResult
{
    public function __construct(
        private string $state,
        private ?string $message = null
    ) {}

    public function state(): string
    {
        return $this->state;
    }

    public function message(): ?string
    {
        return $this->message;
    }
}
