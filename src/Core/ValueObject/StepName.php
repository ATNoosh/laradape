<?php

namespace Core\ValueObject;

final class StepName
{
    public function __construct(private string $value)
    {
        if ($value === '') {
            throw new \InvalidArgumentException('StepName cannot be empty');
        }
    }

    public function value(): string
    {
        return $this->value;
    }
}
