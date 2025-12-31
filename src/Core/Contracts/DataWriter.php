<?php

namespace Core\Contracts;

interface DataWriter
{
    public function write(iterable $data): void;
}
