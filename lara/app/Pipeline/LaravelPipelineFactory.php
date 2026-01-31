<?php

namespace App\Pipeline;

use Core\Pipeline\Pipeline;
use Core\Pipeline\PipelineFactory;
use InvalidArgumentException;

final class LaravelPipelineFactory implements PipelineFactory
{
    public function __construct(
        private array $pipelines // ['import' => fn() => new ImportPipeline()]
    ) {}

    public function make(string $name): Pipeline
    {
        if (! isset($this->pipelines[$name])) {
            throw new InvalidArgumentException("Unknown pipeline [$name]");
        }

        return ($this->pipelines[$name])();
    }
}
