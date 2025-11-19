<?php

declare(strict_types=1);

namespace Lepresk\LaravelOnesignal\Channel;

use Illuminate\Notifications\Notification;
use Lepresk\LaravelOnesignal\Contracts\OneSignalClientInterface;
use Lepresk\LaravelOnesignal\Exceptions\NotificationFailedException;
use Lepresk\LaravelOnesignal\PushMessage;

class PushChannel
{
    /**
     * Create a new push channel instance.
     */
    public function __construct(
        protected OneSignalClientInterface $client
    ) {}

    /**
     * Send the given notification.
     *
     * @throws NotificationFailedException
     */
    public function send(mixed $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toPush')) {
            return;
        }

        /** @var PushMessage|null $message */
        $message = $notification->toPush($notifiable);

        if ($message === null) {
            return;
        }

        $this->client->send($message);
    }
}
