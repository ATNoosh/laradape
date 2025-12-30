<?php

namespace Core\Contract;

interface DataWriter
{
    public function write(iterable $data): void;
}
