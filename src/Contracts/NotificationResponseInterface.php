<?php

declare(strict_types=1);

namespace Lepresk\LaravelOnesignal\Contracts;

interface NotificationResponseInterface
{
    /**
     * Check if the notification was sent successfully.
     */
    public function isSuccessful(): bool;

    /**
     * Get the notification ID from OneSignal.
     */
    public function getNotificationId(): ?string;

    /**
     * Get the number of recipients.
     */
    public function getRecipients(): int;

    /**
     * Get any errors from the response.
     *
     * @return array<string, mixed>
     */
    public function getErrors(): array;

    /**
     * Get the raw response data.
     *
     * @return array<string, mixed>
     */
    public function getRawResponse(): array;

    /**
     * Get the HTTP status code.
     */
    public function getStatusCode(): int;
}
