<?php

namespace Core\Contract;

interface DataReader
{
    public function read(): iterable;
}
