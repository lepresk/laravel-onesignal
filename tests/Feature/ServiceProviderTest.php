<?php

declare(strict_types=1);

namespace Lepresk\LaravelOnesignal\Tests\Feature;

use Lepresk\LaravelOnesignal\Channel\PushChannel;
use Lepresk\LaravelOnesignal\Contracts\OneSignalClientInterface;
use Lepresk\LaravelOnesignal\Facades\OneSignal;
use Lepresk\LaravelOnesignal\Tests\TestCase;

class ServiceProviderTest extends TestCase
{
    public function test_service_provider_registers_client(): void
    {
        $client = $this->app->make(OneSignalClientInterface::class);

        $this->assertInstanceOf(OneSignalClientInterface::class, $client);
    }

    public function test_service_provider_registers_push_channel(): void
    {
        $channel = $this->app->make(PushChannel::class);

        $this->assertInstanceOf(PushChannel::class, $channel);
    }

    public function test_facade_resolves_correctly(): void
    {
        $this->assertInstanceOf(OneSignalClientInterface::class, OneSignal::getFacadeRoot());
    }

    public function test_config_is_published(): void
    {
        $config = config('onesignal');

        $this->assertIsArray($config);
        $this->assertEquals('test-app-id', $config['app_id']);
        $this->assertEquals('test-rest-api-key', $config['rest_api_key']);
    }
}
