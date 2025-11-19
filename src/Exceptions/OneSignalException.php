<?php

declare(strict_types=1);

namespace Lepresk\LaravelOnesignal\Exceptions;

use Exception;

class OneSignalException extends Exception
{
    /**
     * Create a new OneSignal exception instance.
     */
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
