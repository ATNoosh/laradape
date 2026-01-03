<?php

namespace Core\Execution;

enum CircuitState
{
    case CLOSED;
    case OPEN;
    case HALF_OPEN;
}
