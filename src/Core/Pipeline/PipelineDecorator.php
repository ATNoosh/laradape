<?php
namespace Core\Pipeline;

interface PipelineDecorator
{
    public function decorate(Pipeline $pipeline): Pipeline;
}
