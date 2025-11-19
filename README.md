# Laravel OneSignal

[![Latest Version on Packagist](https://img.shields.io/packagist/v/lepresk/laravel-onesignal.svg?style=flat-square)](https://packagist.org/packages/lepresk/laravel-onesignal)
[![Total Downloads](https://img.shields.io/packagist/dt/lepresk/laravel-onesignal.svg?style=flat-square)](https://packagist.org/packages/lepresk/laravel-onesignal)
[![License](https://img.shields.io/packagist/l/lepresk/laravel-onesignal.svg?style=flat-square)](https://packagist.org/packages/lepresk/laravel-onesignal)

A professional OneSignal notification channel for Laravel applications. Send push notifications to iOS, Android, and web browsers through OneSignal's API with a clean, fluent interface.

## Features

- Laravel Notification Channel integration
- Fluent message builder API
- Event system for notification lifecycle
- Comprehensive configuration options
- Custom exception handling
- PSR-3 logging support
- Type-safe implementation with PHP 8.3
- Full test coverage

## Requirements

- PHP 8.3 or higher
- Laravel 11.0 or 12.0
- OneSignal account and API credentials

## Installation

Install the package via Composer:

```bash
composer require lepresk/laravel-onesignal
```

The service provider will be automatically registered through Laravel's package auto-discovery.

### Publish Configuration

Publish the configuration file to customize package behavior:

```bash
php artisan vendor:publish --tag=onesignal-config
```

### Environment Configuration

Add your OneSignal credentials to your `.env` file:

```env
ONESIGNAL_APP_ID=your-app-id
ONESIGNAL_REST_API_KEY=your-rest-api-key
ONESIGNAL_ANDROID_CHANNEL_ID=your-android-channel-id
```

## Usage

### Basic Usage with Facade

```php
use Lepresk\LaravelOnesignal\Facades\OneSignal;
use Lepresk\LaravelOnesignal\PushMessage;

$message = (new PushMessage())
    ->withTitle('Hello World')
    ->withBody('This is a test notification')
    ->toExternalUserIds([1, 2, 3]);

$response = OneSignal::send($message);

if ($response->isSuccessful()) {
    echo "Notification sent! ID: " . $response->getNotificationId();
}
```

### Using Laravel Notifications

Create a notification class:

```php
<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Lepresk\LaravelOnesignal\PushMessage;

class WelcomeNotification extends Notification
{
    public function via($notifiable): array
    {
        return ['onesignal'];
    }

    public function toPush($notifiable): PushMessage
    {
        return (new PushMessage())
            ->withTitle('Welcome!')
            ->withBody('Thanks for joining our platform')
            ->toUser($notifiable->onesignal_id);
    }
}
```

Send the notification:

```php
$user->notify(new WelcomeNotification());
```

### Building Messages

#### Multi-language Support

```php
$message = (new PushMessage())
    ->withTitle('Title', 'en')
    ->withTitle('Titre', 'fr')
    ->withBody('Body', 'en')
    ->withBody('Corps', 'fr');
```

#### Priority Settings

```php
$message = (new PushMessage())
    ->withTitle('Urgent!')
    ->withBody('This is urgent')
    ->withHightPriority();
```

#### Targeting Users

##### External User IDs
External User IDs are custom identifiers you assign to your users in OneSignal. They map your application's user IDs to OneSignal player IDs, allowing you to send notifications to specific users without tracking OneSignal's internal player IDs.

```php
// Single user by external user ID
$message->toUser(123);

// Multiple users by external user IDs
$message->toExternalUserIds([1, 2, 3]);
```

##### Segments
Segments are groups of users defined in your OneSignal dashboard based on various criteria.

```php
// Target a custom segment
$message->toSegment('Premium Users');

// Target multiple segments
$message->toSegments(['Premium Users', 'Active Users']);

// Built-in segments
$message->toSubscribedSegment();  // All subscribed users
$message->toActiveSegment();      // Active users
$message->toInactiveSegment();    // Inactive users
$message->toEngagedSegment();     // Engaged users
```

##### Tags
Tags are key-value pairs you set on user devices to create custom targeting rules.

```php
// Target by tag
$message->toTag('user_type', 'premium');
$message->toTag('subscription_level', 'gold', '=');
```

#### Custom Data

```php
$message = (new PushMessage())
    ->withTitle('New Message')
    ->withBody('You have a new message')
    ->addData('message_id', 456)
    ->addData('conversation_id', 789);
```

#### Images and Buttons

```php
$message = (new PushMessage())
    ->withTitle('Check this out!')
    ->withBody('New image available')
    ->withImage('https://example.com/image.jpg')
    ->addButton('view', 'View Details', 'https://example.com/details')
    ->addButton('dismiss', 'Dismiss');
```

The third parameter in `addButton()` is optional and specifies the URL to open when the button is clicked.

#### Advanced Options

```php
$message = (new PushMessage())
    ->withTitle('Title')
    ->withBody('Body')
    ->setTTL(3600)
    ->withIntelligentDeliveryDelayed()
    ->withName('campaign-2024-01');
```

### Event Listeners

Listen to notification lifecycle events:

```php
use Lepresk\LaravelOnesignal\Events\NotificationSending;
use Lepresk\LaravelOnesignal\Events\NotificationSent;
use Lepresk\LaravelOnesignal\Events\NotificationFailed;

// In your EventServiceProvider
protected $listen = [
    NotificationSending::class => [
        // Handle before sending
    ],
    NotificationSent::class => [
        // Handle after successful send
    ],
    NotificationFailed::class => [
        // Handle when send fails
    ],
];
```

Example listener implementation:

```php
<?php

namespace App\Listeners;

use Lepresk\LaravelOnesignal\Events\NotificationSent;
use Illuminate\Support\Facades\Log;

class LogNotificationSent
{
    public function handle(NotificationSent $event): void
    {
        Log::info('OneSignal notification sent', [
            'notification_id' => $event->response->getNotificationId(),
            'recipients' => $event->response->getRecipients(),
        ]);
    }
}
```

## Configuration

The package provides extensive configuration options in `config/onesignal.php`:

### Default Values

The package applies default values from the configuration file. These defaults are used only when values are not explicitly set on the message.

```php
return [
    'app_id' => env('ONESIGNAL_APP_ID'),
    'rest_api_key' => env('ONESIGNAL_REST_API_KEY'),
    'user_auth_key' => env('ONESIGNAL_USER_AUTH_KEY', ''),
    'android_channel_id' => env('ONESIGNAL_ANDROID_CHANNEL_ID'),

    'defaults' => [
        'ttl' => (int) env('ONESIGNAL_DEFAULT_TTL', 604800),      // 7 days
        'priority' => (int) env('ONESIGNAL_DEFAULT_PRIORITY', 5),  // Normal priority
    ],

    'logging' => [
        'enabled' => (bool) env('ONESIGNAL_LOGGING_ENABLED', true),
        'channel' => env('ONESIGNAL_LOG_CHANNEL'),
        'level' => env('ONESIGNAL_LOG_LEVEL', 'info'),
    ],

    'throw_exceptions' => (bool) env('ONESIGNAL_THROW_EXCEPTIONS', true),

    'http' => [
        'timeout' => (int) env('ONESIGNAL_HTTP_TIMEOUT', 30),
        'retry' => [
            'times' => (int) env('ONESIGNAL_RETRY_TIMES', 3),
            'sleep' => (int) env('ONESIGNAL_RETRY_SLEEP', 1000),
        ],
    ],
];
```

## Testing

Run the test suite:

```bash
composer test
```

Run tests with coverage:

```bash
composer test-coverage
```

## Code Quality

Format code with Laravel Pint:

```bash
composer format
```

Run static analysis with PHPStan:

```bash
composer analyse
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security-related issues, please email lepresk@gmail.com instead of using the issue tracker.

## Credits

- [lepresk](https://github.com/lepresk)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
