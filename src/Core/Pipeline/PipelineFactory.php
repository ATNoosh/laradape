<?php

namespace Core\Pipeline;

interface PipelineFactory {
    public function make(string $name): Pipeline;
}