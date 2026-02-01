<?php

use PHPUnit\Framework\TestCase;
use Core\Pipeline\Pipeline;
use Core\Pipeline\PipelineFactory;
use Core\Pipeline\PipelineContext;
use Core\Pipeline\PipelineResult;

final class PipelineFactoryContractTest extends TestCase
{
    public function test_pipeline_factory_creates_pipeline(): void
    {
        $factory = new class implements PipelineFactory {
            public function make(string $string): Pipeline
            {
                return new class implements Pipeline {

                    public function name(): string
                    {
                        return 'dummy';
                    }

                    public function run(PipelineContext $context): PipelineResult
                    {
                        return PipelineResult::success();
                    }
                };
            }
        };

        $pipeline = $factory->make('test');

        $this->assertInstanceOf(Pipeline::class, $pipeline);
    }
}
