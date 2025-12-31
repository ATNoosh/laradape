<?php

namespace Core\Contracts;

interface DataDestination
{
    public function writer(): DataWriter;
}
