<?php

declare(strict_types=1);

namespace Dibakar\Ownership\Exceptions;

use Exception;

class InvalidOwnerException extends Exception
{
    /**
     * Create a new exception instance.
     *
     * @param  string  $message
     * @param  int  $code
     * @param  \Throwable|null  $previous
     * @return void
     */
    public function __construct(
        string $message = 'The given owner is not valid.',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
