<?php

namespace Core\Job;

use Core\Pipeline\Pipeline;
use Core\ValueObject\JobId;

final class Job
{
    public function __construct(
        private JobId $id,
        private Pipeline $pipeline,
        private string $state = JobState::PENDING,
        private string $queue = 'default',
        private array $payload = []
    ) {}

    public function id(): JobId
    {
        return $this->id;
    }

    public function pipeline(): Pipeline
    {
        return $this->pipeline;
    }

    public function state(): string
    {
        return $this->state;
    }

    public function queue(): string
    {
        return $this->queue;
    }

    /** @return array<string, mixed> */
    public function payload(): array
    {
        return $this->payload;
    }
}
