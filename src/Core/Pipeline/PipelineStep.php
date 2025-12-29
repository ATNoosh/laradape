<?php
interface PipelineStep
{
    public function run(PipelineContext $context): PipelineResult;
}