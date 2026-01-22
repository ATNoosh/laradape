<?php

namespace Core\Execution\Exceptions;

use RuntimeException;
use Core\Execution\NonRetryableException;

final class ValidationException extends RuntimeException implements NonRetryableException
{
}
