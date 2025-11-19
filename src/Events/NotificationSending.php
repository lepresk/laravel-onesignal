<?php

declare(strict_types=1);

namespace Lepresk\LaravelOnesignal\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Lepresk\LaravelOnesignal\PushMessage;

class NotificationSending
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public PushMessage $message,
        public array $payload
    ) {}
}
