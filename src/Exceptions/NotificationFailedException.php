<?php

declare(strict_types=1);

namespace Lepresk\LaravelOnesignal\Exceptions;

class NotificationFailedException extends OneSignalException
{
    /**
     * @var array<string, mixed>
     */
    protected array $errors;

    /**
     * Create a new notification failed exception.
     *
     * @param array<string, mixed> $errors
     */
    public function __construct(
        string $message = 'Failed to send notification',
        array $errors = [],
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
    }

    /**
     * Get the errors from OneSignal.
     *
     * @return array<string, mixed>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
