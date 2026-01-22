<?php

namespace Core\Execution\Exceptions;

use RuntimeException;
use Core\Execution\NonRetryableException;

final class AuthorizationException extends RuntimeException implements NonRetryableException
{
}
