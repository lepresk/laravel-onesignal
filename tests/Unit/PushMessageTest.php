<?php

declare(strict_types=1);

namespace Lepresk\LaravelOnesignal\Tests\Unit;

use Lepresk\LaravelOnesignal\PushMessage;
use PHPUnit\Framework\TestCase;

class PushMessageTest extends TestCase
{
    public function test_it_can_create_a_basic_message(): void
    {
        $message = (new PushMessage)
            ->withTitle('Test Title')
            ->withBody('Test Body');

        $data = $message->build();

        $this->assertEquals(['en' => 'Test Title'], $data['headings']);
        $this->assertEquals(['en' => 'Test Body'], $data['contents']);
    }

    public function test_it_can_set_priority(): void
    {
        $message = (new PushMessage)
            ->withTitle('Test')
            ->withBody('Test')
            ->withHightPriority();

        $data = $message->build();

        $this->assertEquals(PushMessage::PRIORITY_HIGH, $data['priority']);
    }

    public function test_it_can_target_external_user_ids(): void
    {
        $message = (new PushMessage)
            ->withTitle('Test')
            ->withBody('Test')
            ->toExternalUserIds([1, 2, 3]);

        $data = $message->build();

        $this->assertEquals([1, 2, 3], $data['include_external_user_ids']);
    }

    public function test_it_can_add_custom_data(): void
    {
        $message = (new PushMessage)
            ->withTitle('Test')
            ->withBody('Test')
            ->addData('custom_key', 'custom_value');

        $data = $message->build();

        $this->assertEquals('custom_value', $data['data']['custom_key']);
    }

    public function test_it_can_target_by_tag(): void
    {
        $message = (new PushMessage)
            ->withTitle('Test')
            ->withBody('Test')
            ->toTag('user_type', 'premium');

        $data = $message->build();

        $this->assertCount(1, $data['filters']);
        $this->assertEquals('tag', $data['filters'][0]['field']);
        $this->assertEquals('user_type', $data['filters'][0]['key']);
        $this->assertEquals('premium', $data['filters'][0]['value']);
    }

    public function test_it_can_add_image_attachment(): void
    {
        $message = (new PushMessage)
            ->withTitle('Test')
            ->withBody('Test')
            ->withImage('https://example.com/image.jpg');

        $data = $message->build();

        $this->assertEquals(['image' => 'https://example.com/image.jpg'], $data['ios_attachments']);
        $this->assertEquals('https://example.com/image.jpg', $data['big_picture']);
    }

    public function test_it_can_set_ttl(): void
    {
        $message = (new PushMessage)
            ->withTitle('Test')
            ->withBody('Test')
            ->setTTL(3600);

        $data = $message->build();

        $this->assertEquals(3600, $data['ttl']);
    }

    public function test_it_adds_default_notification_type(): void
    {
        $message = (new PushMessage)
            ->withTitle('Test')
            ->withBody('Test');

        $data = $message->build();

        $this->assertEquals(PushMessage::TYPE_NOTIFICATION, $data['data']['type']);
    }

    public function test_it_can_target_segments(): void
    {
        $message = (new PushMessage)
            ->withTitle('Test')
            ->withBody('Test')
            ->toSegment('Premium Users');

        $data = $message->build();

        $this->assertEquals(['Premium Users'], $data['included_segments']);
    }

    public function test_it_can_target_multiple_segments(): void
    {
        $message = (new PushMessage)
            ->withTitle('Test')
            ->withBody('Test')
            ->toSegments(['Premium Users', 'Active Users']);

        $data = $message->build();

        $this->assertEquals(['Premium Users', 'Active Users'], $data['included_segments']);
    }

    public function test_it_can_add_button_with_url(): void
    {
        $message = (new PushMessage)
            ->withTitle('Test')
            ->withBody('Test')
            ->addButton('view', 'View Details', 'https://example.com');

        $data = $message->build();

        $this->assertCount(1, $data['buttons']);
        $this->assertEquals('view', $data['buttons'][0]['id']);
        $this->assertEquals('View Details', $data['buttons'][0]['text']);
        $this->assertEquals('https://example.com', $data['buttons'][0]['url']);
    }

    public function test_it_can_add_button_without_url(): void
    {
        $message = (new PushMessage)
            ->withTitle('Test')
            ->withBody('Test')
            ->addButton('dismiss', 'Dismiss');

        $data = $message->build();

        $this->assertCount(1, $data['buttons']);
        $this->assertEquals('dismiss', $data['buttons'][0]['id']);
        $this->assertEquals('Dismiss', $data['buttons'][0]['text']);
        $this->assertArrayNotHasKey('url', $data['buttons'][0]);
    }
}
