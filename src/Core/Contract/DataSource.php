<?php

namespace Core\Contract;

interface DataSource
{
    public function reader(): DataReader;
}
