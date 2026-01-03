<?php

namespace Core\Job;

final class JobResult
{
    public const STATE_COMPLETED = 'completed';
    public const STATE_FAILED    = 'failed';

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
        public function isCompleted(): bool
    {
        return $this->state === self::STATE_COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->state === self::STATE_FAILED;
    }

}
