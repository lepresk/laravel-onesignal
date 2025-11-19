<?php

declare(strict_types=1);

namespace Lepresk\LaravelOnesignal;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;
use Lepresk\LaravelOnesignal\Channel\PushChannel;
use Lepresk\LaravelOnesignal\Contracts\OneSignalClientInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use OneSignal\Config;
use Symfony\Component\HttpClient\Psr18Client;

class OneSignalServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/onesignal.php',
            'onesignal'
        );

        $this->registerOneSignalClient();
        $this->registerPushChannel();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/onesignal.php' => config_path('onesignal.php'),
            ], 'onesignal-config');
        }

        $this->registerNotificationChannel();
    }

    /**
     * Register the OneSignal client in the container.
     */
    protected function registerOneSignalClient(): void
    {
        $this->app->singleton(OneSignalClientInterface::class, function (Application $app) {
            $config = $app->make('config')->get('onesignal');

            $this->validateConfig($config);

            $oneSignalConfig = new Config(
                $config['app_id'],
                $config['rest_api_key'],
                $config['user_auth_key'] ?? ''
            );

            $httpClient = new Psr18Client;
            $requestFactory = $streamFactory = new Psr17Factory;

            $logger = $config['logging']['channel']
                ? $app->make('log')->channel($config['logging']['channel'])
                : $app->make('log')->getLogger();

            return new OneSignalClient(
                new \OneSignal\OneSignal($oneSignalConfig, $httpClient, $requestFactory, $streamFactory),
                $config,
                $logger,
                $app->make('events')
            );
        });

        $this->app->alias(OneSignalClientInterface::class, 'onesignal');
    }

    /**
     * Register the Push notification channel.
     */
    protected function registerPushChannel(): void
    {
        $this->app->singleton(PushChannel::class, function (Application $app) {
            return new PushChannel(
                $app->make(OneSignalClientInterface::class)
            );
        });
    }

    /**
     * Register the notification channel with Laravel's notification system.
     */
    protected function registerNotificationChannel(): void
    {
        Notification::resolved(function (ChannelManager $service) {
            $service->extend('onesignal', function ($app) {
                return $app->make(PushChannel::class);
            });
        });
    }

    /**
     * Validate the configuration.
     *
     * @param array<string, mixed> $config
     *
     * @throws \Lepresk\LaravelOnesignal\Exceptions\InvalidConfigurationException
     */
    protected function validateConfig(array $config): void
    {
        if (empty($config['app_id'])) {
            throw new Exceptions\InvalidConfigurationException(
                'OneSignal App ID is required. Please set ONESIGNAL_APP_ID in your .env file.'
            );
        }

        if (empty($config['rest_api_key'])) {
            throw new Exceptions\InvalidConfigurationException(
                'OneSignal REST API Key is required. Please set ONESIGNAL_REST_API_KEY in your .env file.'
            );
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            OneSignalClientInterface::class,
            'onesignal',
            PushChannel::class,
        ];
    }
}
