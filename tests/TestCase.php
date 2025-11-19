<?php

declare(strict_types=1);

namespace Lepresk\LaravelOnesignal\Tests;

use Lepresk\LaravelOnesignal\OneSignalServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app): array
    {
        return [
            OneSignalServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('onesignal.app_id', 'test-app-id');
        $app['config']->set('onesignal.rest_api_key', 'test-rest-api-key');
        $app['config']->set('onesignal.android_channel_id', 'test-channel');
    }
}
