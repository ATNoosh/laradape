<?php

namespace App\Execution;

use Core\Repository\JobExecutionStore;
use Core\ValueObject\JobId;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Support\Facades\Cache;

final class LaravelJobExecutionStore implements JobExecutionStore
{
    /** @var array<string, Lock> */
    private array $locks = [];

    private int $ttlSeconds = 300;

    public function acquire(JobId $id): bool
    {
        $lock = Cache::lock($this->lockKey($id), $this->ttlSeconds);

        if (! $lock->get()) {
            return false;
        }

        $this->locks[$id->value()] = $lock;

        return true;
    }

    public function release(JobId $id): void
    {
        $key = $id->value();

        if (isset($this->locks[$key])) {
            $this->locks[$key]->release();
            unset($this->locks[$key]);
        }
    }

    public function markCompleted(JobId $id): void
    {
        Cache::put($this->completedKey($id), true, $this->ttlSeconds);
    }

    public function isCompleted(JobId $id): bool
    {
        return Cache::get($this->completedKey($id), false);
    }

    private function lockKey(JobId $id): string
    {
        return "jobs:lock:{$id->value()}";
    }

    private function completedKey(JobId $id): string
    {
        return "jobs:completed:{$id->value()}";
    }
}
