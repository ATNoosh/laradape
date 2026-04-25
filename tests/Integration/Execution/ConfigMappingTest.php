<?php

namespace Tests\Integration\Execution;

use App\Execution\RetryPolicyFactory;
use App\Execution\TimeBoxFactory;
use Core\Execution\RetryContext;
use Core\Job\Job;
use Core\Job\JobResult;
use Core\Pipeline\Pipeline;
use Core\Pipeline\PipelineContext;
use Core\Pipeline\PipelineResult;
use Core\ValueObject\JobId;
use Orchestra\Testbench\TestCase;

final class ConfigMappingTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            \App\Providers\ExecutionServiceProvider::class,
            \App\Providers\PipelineServiceProvider::class,
        ];
    }

    public function test_retry_policy_factory_maps_config_values(): void
    {
        $this->app['config']->set('jobs.retry.max_attempts', 2);
        $this->app['config']->set('jobs.retry.delay_ms', 150);

        $policy = $this->app->make(RetryPolicyFactory::class)->make();

        $context = new RetryContext(
            job: $this->job(),
            result: JobResult::failed('failed'),
            exception: null,
            attempt: 1,
            startedAt: microtime(true)
        );

        $decision = $policy->decide($context);

        $this->assertTrue($decision->shouldRetry());
        $this->assertSame(150, $decision->delayMs());

        $contextStop = new RetryContext(
            job: $this->job(),
            result: JobResult::failed('failed'),
            exception: null,
            attempt: 2,
            startedAt: microtime(true)
        );

        $decisionStop = $policy->decide($contextStop);

        $this->assertFalse($decisionStop->shouldRetry());
    }

    public function test_timebox_factory_maps_timeout_seconds(): void
    {
        $this->app['config']->set('jobs.timeout_seconds', 0);

        $timeBox = $this->app->make(TimeBoxFactory::class)->make(time());

        $this->assertTrue($timeBox->isExpired());

        $this->app['config']->set('jobs.timeout_seconds', 5);

        $timeBoxFresh = $this->app->make(TimeBoxFactory::class)->make(time());

        $this->assertFalse($timeBoxFresh->isExpired());
    }

    private function job(): Job
    {
        return new Job(
            new JobId('cfg-1'),
            new class implements Pipeline {
                public function name(): string
                {
                    return 'cfg';
                }

                public function run(PipelineContext $context): PipelineResult
                {
                    return PipelineResult::success();
                }
            }
        );
    }
}
