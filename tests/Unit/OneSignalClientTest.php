<?php

declare(strict_types=1);

namespace Lepresk\LaravelOnesignal\Tests\Unit;

use Lepresk\LaravelOnesignal\Exceptions\InvalidMessageException;
use Lepresk\LaravelOnesignal\Exceptions\NotificationFailedException;
use Lepresk\LaravelOnesignal\OneSignalClient;
use Lepresk\LaravelOnesignal\PushMessage;
use Mockery;
use OneSignal\OneSignal;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class OneSignalClientTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_throws_exception_for_empty_contents(): void
    {
        $oneSignal = Mockery::mock(OneSignal::class);
        $config = ['app_id' => 'test', 'android_channel_id' => 'test'];

        $client = new OneSignalClient($oneSignal, $config, new NullLogger);

        $message = new PushMessage;

        $this->expectException(InvalidMessageException::class);
        $this->expectExceptionMessage('Message contents are required');

        $client->send($message);
    }

    public function test_it_throws_exception_for_missing_target_audience(): void
    {
        $oneSignal = Mockery::mock(OneSignal::class);
        $config = ['app_id' => 'test', 'android_channel_id' => 'test'];

        $client = new OneSignalClient($oneSignal, $config, new NullLogger);

        $message = (new PushMessage)
            ->withTitle('Test')
            ->withBody('Test Body');

        $this->expectException(InvalidMessageException::class);
        $this->expectExceptionMessage('At least one target audience must be specified');

        $client->send($message);
    }

    public function test_it_sends_notification_successfully(): void
    {
        $oneSignal = Mockery::mock(OneSignal::class);
        $notifications = Mockery::mock();

        $oneSignal->shouldReceive('notifications')
            ->once()
            ->andReturn($notifications);

        $notifications->shouldReceive('add')
            ->once()
            ->andReturn([
                '_status_code' => 200,
                'id' => 'notification-123',
                'recipients' => 5,
            ]);

        $config = [
            'app_id' => 'test',
            'android_channel_id' => 'test',
            'logging' => ['enabled' => false],
        ];

        $client = new OneSignalClient($oneSignal, $config, new NullLogger);

        $message = (new PushMessage)
            ->withTitle('Test')
            ->withBody('Test Body')
            ->toExternalUserIds([1, 2, 3]);

        $response = $client->send($message);

        $this->assertTrue($response->isSuccessful());
        $this->assertEquals('notification-123', $response->getNotificationId());
        $this->assertEquals(5, $response->getRecipients());
    }

    public function test_it_handles_notification_failure(): void
    {
        $oneSignal = Mockery::mock(OneSignal::class);
        $notifications = Mockery::mock();

        $oneSignal->shouldReceive('notifications')
            ->once()
            ->andReturn($notifications);

        $notifications->shouldReceive('add')
            ->once()
            ->andReturn([
                '_status_code' => 400,
                'errors' => ['Invalid request'],
            ]);

        $config = [
            'app_id' => 'test',
            'android_channel_id' => 'test',
            'logging' => ['enabled' => false],
            'throw_exceptions' => true,
        ];

        $client = new OneSignalClient($oneSignal, $config, new NullLogger);

        $message = (new PushMessage)
            ->withTitle('Test')
            ->withBody('Test Body')
            ->toExternalUserIds([1, 2, 3]);

        $this->expectException(NotificationFailedException::class);

        $client->send($message);
    }
}
