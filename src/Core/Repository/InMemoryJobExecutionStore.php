<?php

namespace Core\Repository;

use Core\ValueObject\JobId;

final class InMemoryJobExecutionStore implements JobExecutionStore
{
    /**
     * @var array<string, bool>
     */
    private array $locks = [];

    /**
     * @var array<string, bool>
     */
    private array $completed = [];

    public function acquire(JobId $jobId): bool
    {
        $id = $jobId->value();

        if (($this->locks[$id] ?? false) === true) {
            return false;
        }

        $this->locks[$id] = true;

        return true;
    }

    public function release(JobId $jobId): void
    {
        unset($this->locks[$jobId->value()]);
    }

    public function isCompleted(JobId $jobId): bool
    {
        return $this->completed[$jobId->value()] ?? false;
    }

    public function markCompleted(JobId $jobId): void
    {
        $this->completed[$jobId->value()] = true;
    }
}
