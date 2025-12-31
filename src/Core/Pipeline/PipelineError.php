<?php

namespace Core\Pipeline;

final class PipelineError
{
    public function __construct(
        private string $code,
        private string $message,
        private array $meta = []
    ) {}

    public function code(): string { return $this->code; }
    public function message(): string { return $this->message; }
    public function meta(): array { return $this->meta; }
}
