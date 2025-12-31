<?php

namespace Core\Contracts;

interface Serializer
{
    public function serialize(mixed $data): string;
    public function deserialize(string $data): mixed;
}
