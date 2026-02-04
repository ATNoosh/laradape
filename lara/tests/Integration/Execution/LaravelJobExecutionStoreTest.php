<?php

namespace Tests\Integration\Execution;

use App\Execution\LaravelJobExecutionStore;
use Core\ValueObject\JobId;
use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Cache;

final class LaravelJobExecutionStoreTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('cache.default', 'array');
    }

    public function test_acquire_lock_once(): void
    {
        $store = new LaravelJobExecutionStore();
        $jobId = new JobId('job-1');

        $this->assertTrue($store->acquire($jobId));
        $this->assertFalse($store->acquire($jobId)); // second worker
    }

    public function test_release_allows_reacquire(): void
    {
        $store = new LaravelJobExecutionStore();
        $jobId = new JobId('job-2');

        $this->assertTrue($store->acquire($jobId));

        $store->release($jobId);

        $this->assertTrue($store->acquire($jobId));
    }

    public function test_mark_completed(): void
    {
        $store = new LaravelJobExecutionStore();
        $jobId = new JobId('job-3');

        $this->assertFalse($store->isCompleted($jobId));

        $store->markCompleted($jobId);

        $this->assertTrue($store->isCompleted($jobId));
    }
}
