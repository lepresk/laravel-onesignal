<?php

declare(strict_types=1);

namespace Lepresk\LaravelOnesignal;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;
use Lepresk\LaravelOnesignal\Channel\PushChannel;
use Lepresk\LaravelOnesignal\Contracts\OneSignalClientInterface;
use Lepresk\LaravelOnesignal\Exceptions\InvalidConfigurationException;
use Nyholm\Psr7\Factory\Psr17Factory;
use OneSignal\Config;
use OneSignal\OneSignal;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Psr18Client;
use Symfony\Component\HttpClient\Retry\GenericRetryStrategy;
use Symfony\Component\HttpClient\RetryableHttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

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

            /** @var non-empty-string $appId */
            $appId = $config['app_id'];
            /** @var non-empty-string $restApiKey */
            $restApiKey = $config['rest_api_key'];
            $userAuthKey = $config['user_auth_key'] ?? null;

            $oneSignalConfig = new Config(
                $appId,
                $restApiKey,
                is_string($userAuthKey) && $userAuthKey !== '' ? $userAuthKey : null
            );

            $psr17Factory = new Psr17Factory;
            $httpClient = new Psr18Client($this->createHttpClient($config['http'] ?? []), $psr17Factory, $psr17Factory);

            $logChannel = $config['logging']['channel'] ?? null;
            $logger = $logChannel
                ? $app->make('log')->channel($logChannel)
                : $app->make('log')->getLogger();

            return new OneSignalClient(
                new OneSignal($oneSignalConfig, $httpClient, $psr17Factory, $psr17Factory),
                $config,
                $logger,
                $app->make('events')
            );
        });

        $this->app->alias(OneSignalClientInterface::class, 'onesignal');
    }

    /**
     * Create the HTTP client with the configured timeout and retry policy.
     *
     * The default retry strategy only retries a POST when OneSignal did not
     * process it (429, 502, 503...), so notifications are never sent twice.
     *
     * @param array<string, mixed> $http
     */
    protected function createHttpClient(array $http): HttpClientInterface
    {
        $timeout = max(1, (int) ($http['timeout'] ?? 30));

        $client = HttpClient::create([
            'timeout' => $timeout,
            'max_duration' => $timeout,
        ]);

        $retries = max(0, (int) ($http['retry']['times'] ?? 0));

        if ($retries === 0) {
            return $client;
        }

        $strategy = new GenericRetryStrategy(
            GenericRetryStrategy::DEFAULT_RETRY_STATUS_CODES,
            max(0, (int) ($http['retry']['sleep'] ?? 1000))
        );

        return new RetryableHttpClient($client, $strategy, $retries);
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
            $service->extend('push', function ($app) {
                return $app->make(PushChannel::class);
            });
        });
    }

    /**
     * Validate the configuration.
     *
     * @param array<string, mixed> $config
     *
     * @throws InvalidConfigurationException
     */
    protected function validateConfig(array $config): void
    {
        if (empty($config['app_id']) || ! is_string($config['app_id'])) {
            throw new InvalidConfigurationException(
                'OneSignal App ID is required. Please set ONESIGNAL_APP_ID in your .env file.'
            );
        }

        if (empty($config['rest_api_key']) || ! is_string($config['rest_api_key'])) {
            throw new InvalidConfigurationException(
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
