<?php

namespace App\Pipeline;

use Core\Pipeline\Pipeline;
use Core\Pipeline\PipelineFactory;

final class LaravelPipelineFactory implements PipelineFactory
{
    public function make(string $name): Pipeline
    {
        // TODO: Implement pipeline creation by name
        // For now, this is a stub implementation
        // In the future, this should resolve pipelines from configuration or container
        throw new \RuntimeException(
            "PipelineFactory::make() is not yet implemented. Pipeline '{$name}' cannot be created."
        );
    }
}

