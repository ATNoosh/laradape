<?php
namespace Core\Pipeline;

interface Pipeline
{
    public function run(PipelineContext $context): PipelineResult;
}
