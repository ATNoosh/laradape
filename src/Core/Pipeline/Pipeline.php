<?php
interface Pipeline
{
    public function run(PipelineContext $context): PipelineResult;
}
