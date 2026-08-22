<?php

namespace App\Pipeline;

use Core\Pipeline\Pipeline;

interface PayloadAwarePipeline extends Pipeline
{
    /** @param array<string, mixed> $payload */
    public function withPayload(array $payload): self;
}
