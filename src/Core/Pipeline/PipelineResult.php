<?php

namespace Core\Pipeline;


final class PipelineResult
{
    private bool $success;
    private array $data;
    private ?PipelineError $error;

    private function __construct(bool $success, array $data = [], ?PipelineError $error = null)
    {
        $this->success = $success;
        $this->data = $data;
        $this->error = $error;
    }

    public static function success(array $data = []): self
    {
        return new self(true, $data);
    }

    public static function failure(PipelineError $error): self
    {
        return new self(false, [], $error);
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function data(): array
    {
        return $this->data;
    }

    public function error(): ?PipelineError
    {
        return $this->error;
    }
}

