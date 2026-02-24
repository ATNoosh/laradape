<?php

namespace App\Http\Responses;

final class ApiResponse implements \JsonSerializable
{
    public function __construct(
        private mixed $data = null,
        private mixed $error = null,
        private array $meta = []
    ) {}

    public static function success(mixed $data, array $meta = []): self
    {
        return new self($data, null, $meta);
    }

    public static function error(mixed $error, array $meta = []): self
    {
        return new self(null, $error, $meta);
    }

    public function jsonSerialize(): array
    {
        return [
            'data' => $this->data,
            'error' => $this->error,
            'meta' => $this->meta,
        ];
    }
}
