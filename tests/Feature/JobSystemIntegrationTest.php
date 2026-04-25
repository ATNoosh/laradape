<?php

namespace Tests\Feature;

use Core\Execution\Executor;
use Core\Execution\ExecutorFactory;
use Tests\TestCase;
use Core\Job\Job;
use Core\Job\JobResult;
use Core\Execution\JobDispatcherInterface;
use Core\Execution\JobReport;
use Core\Pipeline\Pipeline;
use Core\Pipeline\PipelineContext;
use Core\Pipeline\PipelineFactory;
use Core\Pipeline\PipelineResult;
use Core\ValueObject\JobId;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class JobSystemIntegrationTest extends TestCase
{
    use RefreshDatabase;
    public function test_job_system_bindings_are_resolvable(): void
    {
        $this->assertTrue($this->app->bound(JobDispatcherInterface::class));
        $this->assertTrue($this->app->bound(ExecutorFactory::class));
        $this->assertTrue($this->app->bound(PipelineFactory::class));
    }

    public function test_dispatcher_can_dispatch_a_job(): void
    {
        $this->app->bind(Executor::class, fn () =>
            new class implements Executor {
                public function execute(Job $job): JobReport
                {
                    return JobReport::completed($job->id());
                }
            }
        );

        $dispatcher = $this->app->make(JobDispatcherInterface::class);

        $job = new Job(
            new JobId('smoke-job'),
            new class implements Pipeline {
                public function name(): string { return 'dummy'; }
                public function run(PipelineContext $context): PipelineResult
                {
                    return PipelineResult::success();
                }
            }
        );

        $this->assertTrue(
            $dispatcher->dispatch($job)->result()->isCompleted()
        );
    }
    public function test_job_system_executes_a_job_end_to_end(): void
    {
        // Arrange
        $dispatcher = $this->app->make(JobDispatcherInterface::class);

        $job = new Job(
            new JobId('integration-job'),
            new class implements Pipeline {

                public function name(): string
                {
                    return 'dummy';
                }

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
