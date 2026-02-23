<?php

namespace App\Http\Responses;

final class ErrorResponse implements \JsonSerializable
{
    public function __construct(
        private string $code,
        private string $message,
        private array $details = []
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'code' => $this->code,
            'message' => $this->message,
            'details' => $this->details,
        ];
    }
}
