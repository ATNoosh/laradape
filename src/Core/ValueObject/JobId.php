<?php

namespace Core\ValueObject;

final class JobId
{
    public function __construct(private string $value)
    {
        if ($value === '') {
            throw new \InvalidArgumentException('JobId cannot be empty');
        }
    }

    public function value(): string
    {
        return $this->value;
    }
}
