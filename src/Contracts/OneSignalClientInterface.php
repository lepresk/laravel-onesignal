<?php

declare(strict_types=1);

namespace Lepresk\LaravelOnesignal\Contracts;

use Lepresk\LaravelOnesignal\PushMessage;

interface OneSignalClientInterface
{
    /**
     * Send a push notification.
     *
     * @throws \Lepresk\LaravelOnesignal\Exceptions\NotificationFailedException
     */
    public function send(PushMessage $message): NotificationResponseInterface;

    /**
     * Get the OneSignal app ID.
     */
    public function getAppId(): string;

    /**
     * Get the Android channel ID.
     */
    public function getAndroidChannelId(): ?string;
}
