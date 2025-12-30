<?php
namespace Core\Pipeline;

interface PipelineStep
{
    public function execute(PipelineContext $context): PipelineResult;
}