<?php

declare(strict_types=1);

namespace Lepresk\LaravelOnesignal\Facades;

use Illuminate\Support\Facades\Facade;
use Lepresk\LaravelOnesignal\Contracts\NotificationResponseInterface;
use Lepresk\LaravelOnesignal\Contracts\OneSignalClientInterface;
use Lepresk\LaravelOnesignal\PushMessage;

/**
 * @method static NotificationResponseInterface send(PushMessage $message)
 * @method static string getAppId()
 * @method static string|null getAndroidChannelId()
 *
 * @see OneSignalClientInterface
 */
class OneSignal extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return OneSignalClientInterface::class;
    }
}
