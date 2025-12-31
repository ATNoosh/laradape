<?php

use PHPUnit\Framework\TestCase;
use Core\Pipeline\DefaultPipeline;
use Core\Pipeline\PipelineContext;
use Core\Pipeline\PipelineResult;
use Core\Pipeline\PipelineStep;

final class DefaultPipelineTest extends TestCase
{
    public function test_pipeline_executes_steps_in_order(): void
    {
        $pipeline = new DefaultPipeline([
            new class implements PipelineStep {
                public function execute(PipelineContext $context): PipelineResult {
                    $context->set('value', 1);
                    return PipelineResult::success();
                }
            },
            new class implements PipelineStep {
                public function execute(PipelineContext $context): PipelineResult {
                    $context->set('value', $context->get('value') + 1);
                    return PipelineResult::success();
                }
            },
        ]);

        $context = new PipelineContext();
        $pipeline->run($context);

        $this->assertSame(2, $context->get('value'));
    }
}
