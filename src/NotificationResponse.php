<?php

declare(strict_types=1);

namespace Lepresk\LaravelOnesignal;

use Lepresk\LaravelOnesignal\Contracts\NotificationResponseInterface;

class NotificationResponse implements NotificationResponseInterface
{
    /**
     * @param array<string, mixed> $response
     */
    public function __construct(
        protected array $response,
        protected int $statusCode
    ) {}

    /**
     * {@inheritdoc}
     */
    public function isSuccessful(): bool
    {
        return $this->statusCode === 200 && empty($this->response['errors']);
    }

    /**
     * {@inheritdoc}
     */
    public function getNotificationId(): ?string
    {
        return $this->response['id'] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function getRecipients(): int
    {
        return (int) ($this->response['recipients'] ?? 0);
    }

    /**
     * {@inheritdoc}
     */
    public function getErrors(): array
    {
        return $this->response['errors'] ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function getRawResponse(): array
    {
        return $this->response;
    }

    /**
     * {@inheritdoc}
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
