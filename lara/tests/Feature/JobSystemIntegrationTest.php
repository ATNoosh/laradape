<?php

namespace Tests\Feature;

use Tests\TestCase;
use Core\Job\Job;
use Core\Job\JobResult;
use Core\Execution\JobDispatcherInterface;
use Core\Pipeline\Pipeline;
use Core\Pipeline\PipelineContext;
use Core\Pipeline\PipelineResult;
use Core\ValueObject\JobId;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class JobSystemIntegrationTest extends TestCase
{
    public function test_job_system_executes_a_job_end_to_end(): void
    {
        // Arrange
        $dispatcher = $this->app->make(JobDispatcherInterface::class);

        $job = new Job(
            new JobId('integration-job'),
            new class implements Pipeline {
                public function run(PipelineContext $context): PipelineResult
                {
                    $context->set('value', 42);
                    return PipelineResult::success();
                }
            }
        );

        // Act
        $report = $dispatcher->dispatch($job);
        $result = $report->result();

        // Assert
        $this->assertInstanceOf(JobResult::class, $result);
        $this->assertSame('completed', $result->state());
        $this->assertNull($result->message());
    }
}
