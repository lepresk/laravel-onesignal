<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | OneSignal App ID
    |--------------------------------------------------------------------------
    |
    | Your OneSignal App ID. You can find this in your OneSignal dashboard
    | under Settings > Keys & IDs.
    |
    */
    'app_id' => env('ONESIGNAL_APP_ID'),

    /*
    |--------------------------------------------------------------------------
    | OneSignal REST API Key
    |--------------------------------------------------------------------------
    |
    | Your OneSignal REST API Key. This is required for sending notifications.
    | You can find this in your OneSignal dashboard under Settings > Keys & IDs.
    |
    */
    'rest_api_key' => env('ONESIGNAL_REST_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | OneSignal User Auth Key (Optional)
    |--------------------------------------------------------------------------
    |
    | Your OneSignal User Auth Key. This is optional and only required for
    | certain API operations like managing apps.
    |
    */
    'user_auth_key' => env('ONESIGNAL_USER_AUTH_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Android Channel ID
    |--------------------------------------------------------------------------
    |
    | The default Android notification channel ID. This is required for
    | Android 8.0+ devices. If not set, notifications may not be delivered
    | properly on newer Android devices.
    |
    */
    'android_channel_id' => env('ONESIGNAL_ANDROID_CHANNEL_ID'),

    /*
    |--------------------------------------------------------------------------
    | Default Settings
    |--------------------------------------------------------------------------
    |
    | Default settings for notifications sent through this package.
    |
    */
    'defaults' => [
        /*
         * Default time-to-live (TTL) for notifications in seconds.
         * OneSignal will attempt to deliver the notification for this duration.
         * Default: 604800 seconds (7 days)
         */
        'ttl' => (int) env('ONESIGNAL_DEFAULT_TTL', 604800),

        /*
         * Default priority for notifications.
         * 10 = High priority, 5 = Normal priority
         */
        'priority' => (int) env('ONESIGNAL_DEFAULT_PRIORITY', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how OneSignal notifications are logged.
    |
    */
    'logging' => [
        /*
         * Enable or disable logging of OneSignal notifications.
         */
        'enabled' => (bool) env('ONESIGNAL_LOGGING_ENABLED', true),

        /*
         * Log channel to use for OneSignal logs.
         * Set to null to use the default application log channel.
         */
        'channel' => env('ONESIGNAL_LOG_CHANNEL'),

        /*
         * Log level for successful notifications.
         * Options: debug, info, notice, warning, error, critical, alert, emergency
         */
        'level' => env('ONESIGNAL_LOG_LEVEL', 'info'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Exception Handling
    |--------------------------------------------------------------------------
    |
    | Configure how exceptions are handled when sending notifications fails.
    |
    */

    /*
     * Whether to throw exceptions when notification sending fails.
     * If false, failures will be logged but no exception will be thrown.
     */
    'throw_exceptions' => (bool) env('ONESIGNAL_THROW_EXCEPTIONS', true),

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the HTTP client used to communicate with OneSignal API.
    |
    */
    'http' => [
        /*
         * Request timeout in seconds.
         */
        'timeout' => (int) env('ONESIGNAL_HTTP_TIMEOUT', 30),

        /*
         * Number of retry attempts for failed requests.
         */
        'retry' => [
            'times' => (int) env('ONESIGNAL_RETRY_TIMES', 3),
            'sleep' => (int) env('ONESIGNAL_RETRY_SLEEP', 1000), // milliseconds
        ],
    ],
];
