<?php

namespace Core\Contract;

interface DataDestination
{
    public function writer(): DataWriter;
}
