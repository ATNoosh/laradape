<?php

namespace Core\Contracts;

interface DataSource
{
    public function reader(): DataReader;
}
