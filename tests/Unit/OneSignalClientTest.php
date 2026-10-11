<?php

declare(strict_types=1);

namespace Lepresk\LaravelOnesignal\Tests\Unit;

use Illuminate\Contracts\Events\Dispatcher;
use Lepresk\LaravelOnesignal\Events\NotificationFailed;
use Lepresk\LaravelOnesignal\Exceptions\InvalidMessageException;
use Lepresk\LaravelOnesignal\Exceptions\NotificationFailedException;
use Lepresk\LaravelOnesignal\OneSignalClient;
use Lepresk\LaravelOnesignal\PushMessage;
use Mockery;
use Nyholm\Psr7\Factory\Psr17Factory;
use OneSignal\Config;
use OneSignal\OneSignal;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Psr\Log\NullLogger;
use Stringable;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Psr18Client;
use Symfony\Component\HttpClient\Response\MockResponse;

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

    public function test_it_rejects_aliases_combined_with_segments(): void
    {
        $client = new OneSignalClient(Mockery::mock(OneSignal::class), ['app_id' => 'test']);

        $this->expectException(InvalidMessageException::class);
        $this->expectExceptionMessage('cannot be combined');

        $client->send((new PushMessage)->withBody('Test')->toUser(1)->toActiveSegment());
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

    public function test_it_does_not_log_message_contents_or_recipients(): void
    {
        $oneSignal = $this->mockOneSignal(['_status_code' => 200, 'id' => 'abc', 'recipients' => 1]);
        $logger = $this->memoryLogger();

        $client = new OneSignalClient($oneSignal, ['app_id' => 'test', 'logging' => ['enabled' => true, 'level' => 'debug']], $logger);

        $client->send((new PushMessage)->withTitle('Secret title')->withBody('Secret body')->toUser('user@example.com'));

        $logged = json_encode($logger->records);
        $this->assertStringNotContainsString('Secret', (string) $logged);
        $this->assertStringNotContainsString('user@example.com', (string) $logged);
        $this->assertSame('debug', $logger->records[0]['level']);
    }

    public function test_it_dispatches_failed_event_when_exceptions_are_disabled(): void
    {
        $oneSignal = $this->mockOneSignal(['_status_code' => 400, 'errors' => ['Invalid request']]);
        $events = Mockery::mock(Dispatcher::class);
        $events->shouldReceive('dispatch')->once()->ordered();
        $events->shouldReceive('dispatch')->once()->with(Mockery::type(NotificationFailed::class))->ordered();

        $client = new OneSignalClient($oneSignal, ['app_id' => 'test', 'logging' => ['enabled' => false], 'throw_exceptions' => false], new NullLogger, $events);

        $response = $client->send((new PushMessage)->withBody('Test')->toUser(1));

        $this->assertFalse($response->isSuccessful());
    }

    public function test_it_sends_a_minimal_message_through_the_real_sdk(): void
    {
        $requests = [];
        $http = new MockHttpClient(function (string $method, string $url, array $options) use (&$requests) {
            $requests[] = compact('method', 'url', 'options');

            return new MockResponse('{"id":"abc","recipients":1}', [
                'http_code' => 200,
                'response_headers' => ['content-type' => 'application/json'],
            ]);
        });

        $factory = new Psr17Factory;
        $oneSignal = new OneSignal(new Config('app-id', 'os_v2_app_key'), new Psr18Client($http, $factory, $factory), $factory, $factory);
        $client = new OneSignalClient($oneSignal, ['app_id' => 'app-id', 'logging' => ['enabled' => false]]);

        $response = $client->send((new PushMessage)->withTitle('Hi')->withBody('Hello')->toUser(7));

        $this->assertTrue($response->isSuccessful());
        $this->assertSame('POST', $requests[0]['method']);
        $this->assertStringStartsWith('https://api.onesignal.com/', $requests[0]['url']);

        $payload = json_decode($requests[0]['options']['body'], true);
        $this->assertSame(['external_id' => ['7']], $payload['include_aliases']);
        $this->assertSame('push', $payload['target_channel']);
        $this->assertArrayNotHasKey('android_channel_id', $payload);
    }

    /**
     * @param array<string, mixed> $response
     */
    private function mockOneSignal(array $response): OneSignal
    {
        $notifications = Mockery::mock();
        $notifications->shouldReceive('add')->once()->andReturn($response);

        $oneSignal = Mockery::mock(OneSignal::class);
        $oneSignal->shouldReceive('notifications')->once()->andReturn($notifications);

        return $oneSignal;
    }

    private function memoryLogger(): AbstractLogger
    {
        return new class extends AbstractLogger
        {
            /** @var array<int, array<string, mixed>> */
            public array $records = [];

            public function log($level, string|Stringable $message, array $context = []): void
            {
                $this->records[] = ['level' => $level, 'message' => (string) $message, 'context' => $context];
            }
        };
    }
}
