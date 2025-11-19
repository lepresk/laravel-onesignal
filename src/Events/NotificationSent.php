<?php

declare(strict_types=1);

namespace Lepresk\LaravelOnesignal\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Lepresk\LaravelOnesignal\Contracts\NotificationResponseInterface;
use Lepresk\LaravelOnesignal\PushMessage;

class NotificationSent
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public PushMessage $message,
        public NotificationResponseInterface $response
    ) {}
}
