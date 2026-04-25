<?php

namespace Tests\Integration\Execution;

use Core\Execution\JobDispatcherInterface;
use Core\Job\Job;
use Core\Pipeline\Pipeline;
use Core\Pipeline\PipelineContext;
use Core\Pipeline\PipelineResult;
use Core\ValueObject\JobId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobDispatchWithDatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_is_persisted_and_executed(): void
    {
        // Arrange
        $dispatcher = $this->app->make(JobDispatcherInterface::class);

        $job = new Job(
            new JobId('1'),
            new class implements Pipeline {

                public function name(): string
                {
                    return 'dummy';
                }

                public function run(PipelineContext $context): PipelineResult
                {
                    $context->set('executed', true);
                    return PipelineResult::success();
                }
            }
        );

        // Act
        $report = $dispatcher->dispatch($job);

        // Assert – execution
        $this->assertTrue($report->result()->isCompleted());

        // Assert – persistence
        $this->assertDatabaseHas('jobs', [
            'id' => '1',
            'pipeline_name' => 'dummy',
        ]);
    }
}
