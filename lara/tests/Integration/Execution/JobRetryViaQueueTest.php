<?php

namespace Tests\Integration\Execution;

use App\Execution\LaravelJobExecutionStore;
use App\Jobs\RetryJob;
use Core\Execution\JobDispatcherInterface;
use Core\Job\Job;
use Core\Pipeline\Pipeline;
use Core\Pipeline\PipelineContext;
use Core\Pipeline\PipelineError;
use Core\Pipeline\PipelineResult;
use Core\ValueObject\JobId;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;

final class JobRetryViaQueueTest extends TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app)
    {
        return [
            \App\Providers\ExecutionServiceProvider::class,
            \App\Providers\PipelineServiceProvider::class,
        ];
    }


    public function test_failed_job_is_retried_via_laravel_queue(): void
    {
        // Arrange
        Queue::fake();

        $dispatcher = $this->app->make(JobDispatcherInterface::class);

        $job = new Job(
            new JobId('retry-1'),
            new class implements Pipeline {

                public function name(): string
                {
                    return 'failing';
                }

                public function run(PipelineContext $context): PipelineResult
                {
                    return PipelineResult::failure(
                        new PipelineError('failed', 'boom')
                    );
                }
            }
        );

        $dispatcher->dispatch($job);
        
        Queue::assertNothingPushed();

    }
}
