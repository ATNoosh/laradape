<?php

use PHPUnit\Framework\TestCase;
use Core\Repository\InMemoryJobExecutionStore;
use Core\ValueObject\JobId;

final class InMemoryJobExecutionStoreTest extends TestCase
{
    public function test_acquire_and_release_lock(): void
    {
        $store = new InMemoryJobExecutionStore();
        $jobId = new JobId('job-1');

        $this->assertTrue($store->acquire($jobId));
        $this->assertFalse($store->acquire($jobId));

        $store->release($jobId);

        $this->assertTrue($store->acquire($jobId));
    }

    public function test_mark_and_check_completed(): void
    {
        $store = new InMemoryJobExecutionStore();
        $jobId = new JobId('job-2');

        $this->assertFalse($store->isCompleted($jobId));

        $store->markCompleted($jobId);

        $this->assertTrue($store->isCompleted($jobId));
    }

    public function test_completed_job_does_not_block_lock(): void
    {
        $store = new InMemoryJobExecutionStore();
        $jobId = new JobId('job-3');

        $store->markCompleted($jobId);

        $this->assertTrue($store->isCompleted($jobId));
        $this->assertTrue($store->acquire($jobId));
    }
}
