<?php

namespace Core\Contracts;

interface DataReader
{
    public function read(): iterable;
}
