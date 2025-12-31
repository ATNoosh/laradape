<?php

use Core\Pipeline\PipelineError;
use PHPUnit\Framework\TestCase;
use Core\Pipeline\PipelineResult;

final class PipelineResultContractTest extends TestCase
{
    public function test_success_result_has_no_error(): void
    {
        $result = PipelineResult::success();

        $this->assertTrue($result->isSuccess());
        $this->assertNull($result->error());
    }

    public function test_failure_result_has_error(): void
    {
        $error = new PipelineError('FAIL', 'failed');

        $result = PipelineResult::failure($error);

        $this->assertFalse($result->isSuccess());
        $this->assertSame($error, $result->error());
    }
    public function test_pipeline_result_is_immutable(): void
    {
        $result = PipelineResult::success();

        $this->assertFalse(
            method_exists($result, 'setSuccess')
        );
    }
}
