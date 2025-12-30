<?php

namespace Core\Job;

use Core\Pipeline\Pipeline;
use Core\ValueObject\JobId;

final class Job
{
    public function __construct(
        private JobId $id,
        private Pipeline $pipeline,
        private string $state = JobState::PENDING
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
}
