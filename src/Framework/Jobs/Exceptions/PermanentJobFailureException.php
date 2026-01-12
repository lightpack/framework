<?php

namespace Lightpack\Jobs\Exceptions;

use RuntimeException;

/**
 * Exception thrown when a job encounters a permanent failure that should not be retried.
 */
class PermanentJobFailureException extends RuntimeException
{
}
