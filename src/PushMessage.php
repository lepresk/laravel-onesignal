<?php

declare(strict_types=1);

namespace Lepresk\LaravelOnesignal;

use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, mixed>
 */
class PushMessage implements Arrayable
{
    public const string TYPE_NOTIFICATION = 'notification';

    public const int PRIORITY_HIGH = 10;

    public const int PRIORITY_NORMAL = 5;

    /**
     * @var array<string, string> The notification contents by language.
     */
    private array $contents;

    /**
     * @var array<string, string> The notification headings by language.
     */
    private array $headings;

    /**
     * @var array<string, mixed> Data to be sent in the push notification.
     */
    private array $data;

    /**
     * @var int|null Time to live in seconds.
     */
    private ?int $ttl = null;

    /**
     * @var string|null The ID of the Android notification channel to send the notification to.
     */
    private ?string $androidChannelId = null;

    /**
     * @var int|null The priority of the notification.
     */
    private ?int $priority = null;

    /**
     * @var array<int, array<string, mixed>> Filters that define the target audience of the notification.
     */
    private array $filters;

    /**
     * @var array<int, array<string, string>> An array of buttons to include in the notification.
     */
    private array $buttons;

    /**
     * @var array<string, string> an array of images attachment to include in the notification
     */
    private array $attachments;

    /**
     * @var array<int, int> an array of externals userIds
     */
    private array $externalUserIds;

    /**
     * @var array<int, string> an array of segment names to target
     */
    private array $segments;

    /**
     * @var string|null The delay to apply to notification
     *
     * @see https://documentation.onesignal.com/reference/create-notification#form-createNotification
     */
    private ?string $delayedOption = null;

    /**
     * @var string|null The name of this notification
     */
    private ?string $name = null;

    /**
     * NotificationObject constructor.
     */
    public function __construct()
    {
        $this->contents = [];
        $this->headings = [];
        $this->data = [];
        $this->filters = [];
        $this->buttons = [];
        $this->attachments = [];
        $this->externalUserIds = [];
        $this->segments = [];
    }

    /**
     * Sets the notification contents by language.
     *
     * @param array<string, string> $contents The contents of the notification.
     *
     * @return $this This instance for method chaining.
     */
    public function setContents(array $contents): self
    {
        $this->contents = $contents;

        return $this;
    }

    /**
     * Adds a new content for the specified language.
     *
     * @param string $value The language of the content to add.
     * @param string $language The value of the content to add.
     *
     * @return $this This instance for method chaining.
     */
    public function withBody(string $value, string $language = 'en'): self
    {
        $this->contents[$language] = $value;
        $this->addData('message', $value);

        return $this;
    }

    /**
     * Adds a new data field to the notification.
     *
     * @param string $key The key of the data field to add.
     * @param mixed $value The value of the data field to add.
     *
     * @return $this This instance for method chaining.
     */
    public function addData(string $key, mixed $value): self
    {
        $this->data[$key] = $value;

        return $this;
    }

    /**
     * Sets the notification headings by language.
     *
     * @param array<string, string> $headings The headings of the notification.
     *
     * @return $this This instance for method chaining.
     */
    public function setHeadings(array $headings): self
    {
        $this->headings = $headings;

        return $this;
    }

    /**
     * Adds a new heading for the specified language.
     *
     * @param string $value The language of the heading to add.
     * @param string $language The value of the heading to add.
     *
     * @return $this This instance for method chaining.
     */
    public function withTitle(string $value, string $language = 'en'): self
    {
        $this->headings[$language] = $value;
        $this->addData('title', $value);

        return $this;
    }

    /**
     * Sets the data to be sent in the push notification.
     *
     * @param array<string, mixed> $data The data to send in the notification.
     *
     * @return $this This instance for method chaining.
     */
    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Sets the time to live in seconds.
     *
     * @param int|null $ttl The time to live in seconds.
     *
     * @return $this This instance for method chaining.
     */
    public function setTTL(?int $ttl): self
    {
        $this->ttl = $ttl;

        return $this;
    }

    /**
     * Gets the current TTL value.
     */
    public function getTTL(): ?int
    {
        return $this->ttl;
    }

    /**
     * Sets the ID of the Android notification channel to send the notification to.
     *
     * @param string|null $androidChannelId The ID of the Android notification channel.
     *
     * @return $this This instance for method chaining.
     */
    public function setAndroidChannelId(?string $androidChannelId): self
    {
        $this->androidChannelId = $androidChannelId;

        return $this;
    }

    /**
     * Gets the current Android channel ID.
     */
    public function getAndroidChannelId(): ?string
    {
        return $this->androidChannelId;
    }

    /**
     * Sets default proirity
     */
    public function withDefaultPriority(): self
    {
        $this->setPriority(self::PRIORITY_NORMAL);

        return $this;
    }

    /**
     * Sets the priority of the notification.
     *
     * @param int $priority The priority of the notification.
     *
     * @return $this This instance for method chaining.
     */
    public function setPriority(int $priority): self
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * Gets the current priority value.
     */
    public function getPriority(): ?int
    {
        return $this->priority;
    }

    /**
     * Sets high priority for this notification
     */
    public function withHightPriority(): self
    {
        $this->setPriority(self::PRIORITY_HIGH);

        return $this;
    }

    /**
     * Sets the filters that define the target audience of the notification.
     *
     * @param array<int, array<string, mixed>> $filters The filters defining the target audience.
     *
     * @return $this This instance for method chaining.
     */
    public function setFilters(array $filters): self
    {
        $this->filters = $filters;

        return $this;
    }

    /**
     * Sets the buttons to include in the notification.
     *
     * @param array<int, array<string, string>> $buttons The buttons to include in the notification.
     *
     * @return $this This instance for method chaining.
     */
    public function setButtons(array $buttons): self
    {
        $this->buttons = $buttons;

        return $this;
    }

    /**
     * Adds a new button to the notification.
     *
     * @param string $id The button id to add to the notification.
     * @param string $text The button text
     * @param string|null $url Optional URL to open when button is clicked
     *
     * @return $this This instance for method chaining.
     */
    public function addButton(string $id, string $text, ?string $url = null): self
    {
        $button = ['id' => $id, 'text' => $text];

        if ($url !== null) {
            $button['url'] = $url;
        }

        $this->buttons[] = $button;

        return $this;
    }

    /**
     * Target devices by a specific tag.
     *
     * @param string $key The tag key to filter by
     * @param string $value The tag value to match
     * @param string $relation The relation operator (=, !=, >, <, exists, not_exists)
     */
    public function toTag(string $key, string $value, string $relation = '='): self
    {
        $this->addFilter([
            'field' => 'tag',
            'key' => $key,
            'relation' => $relation,
            'value' => $value,
        ]);

        return $this;
    }

    /**
     * Target a specific segment by name.
     *
     * @param string $segmentName The name of the segment to target
     */
    public function toSegment(string $segmentName): self
    {
        $this->segments[] = $segmentName;

        return $this;
    }

    /**
     * Target multiple segments by name.
     *
     * @param array<int, string> $segmentNames Array of segment names to target
     */
    public function toSegments(array $segmentNames): self
    {
        $this->segments = array_merge($this->segments, $segmentNames);

        return $this;
    }

    /**
     * Target all subscribed devices.
     */
    public function toSubscribedSegment(): self
    {
        return $this->toSegment('Subscribed Users');
    }

    /**
     * Target active devices.
     */
    public function toActiveSegment(): self
    {
        return $this->toSegment('Active Users');
    }

    /**
     * Target inactive devices.
     */
    public function toInactiveSegment(): self
    {
        return $this->toSegment('Inactive Users');
    }

    /**
     * Target engaged devices.
     */
    public function toEngagedSegment(): self
    {
        return $this->toSegment('Engaged Users');
    }

    /**
     * Adds a new filter to the target audience of the notification.
     *
     * @param array<string, mixed> $filter The filter to add to the target audience.
     *
     * @return $this This instance for method chaining.
     */
    public function addFilter(array $filter): self
    {
        $this->filters[] = $filter;

        return $this;
    }

    /**
     * Add imageUrl to notification data
     *
     * @param string|null $imageUrl The url of image to apply
     */
    public function withImage(?string $imageUrl): self
    {
        if ($imageUrl != null) {
            $this->addData('imageUrl', $imageUrl);
            $this->attachments['image'] = $imageUrl;
        }

        return $this;
    }

    /**
     * set type `Notification::TYPE_NOTIFICATION` as a type og this notification
     *
     * @see Notification::TYPE_NOTIFICATION
     */
    public function withNotificationType(): self
    {
        return $this->withType(self::TYPE_NOTIFICATION);
    }

    /**
     * Set type data to notification
     *
     * @param string $notificationType The to apply
     */
    public function withType(string $notificationType): self
    {
        $this->addData('type', $notificationType);

        return $this;
    }

    /**
     * Attach `log_key` data to notification
     *
     * @param string $logKey The notification log key
     */
    public function withLogKey(string $logKey): self
    {
        $this->addData('log_key', $logKey);

        return $this;
    }

    /**
     * Attach `notification_id` data to notification
     *
     * @param int $notificationId The id of internal notification system
     */
    public function withNotificationId(int $notificationId): self
    {
        $this->addData('notification_id', $notificationId);

        return $this;
    }

    /**
     * Set last-active as delay of this notification
     *
     * @see https://documentation.onesignal.com/docs/sending-notifications#intelligent-delivery
     */
    public function withIntelligentDeliveryDelayed(): self
    {
        $this->delayedOption = 'last-active';

        return $this;
    }

    /**
     * Sets the name of this notification
     *
     * @param string $name The name to apply
     */
    public function withName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Set specified $userId as externalUserId target
     *
     * @param int $oneSignalExternalUserId The id of user to target
     */
    public function toUser(int $oneSignalExternalUserId): self
    {
        $this->externalUserIds[] = $oneSignalExternalUserId;

        return $this;
    }

    /**
     * Set specified userIds as notification target
     *
     * @param array<int, int> $oneSignalExternalUserIds The list of user ids to target
     */
    public function toExternalUserIds(array $oneSignalExternalUserIds): self
    {
        $this->externalUserIds = $oneSignalExternalUserIds;

        return $this;
    }

    public function toArray(): array
    {
        return $this->build();
    }

    /**
     * Builds the notification object.
     *
     * @return array<string, mixed> The built notification object.
     */
    public function build(): array
    {
        if (empty($this->data['notification'])) {
            $this->withNotificationId(-1);
        }

        if (empty($this->data['type'])) {
            $this->withNotificationType();
        }

        $data = [
            'contents' => $this->contents,
            'headings' => $this->headings,
            'data' => $this->data,
            'ttl' => $this->ttl,
            'android_channel_id' => $this->androidChannelId,
            'priority' => $this->priority,
            'filters' => $this->filters,
            'buttons' => $this->buttons,
        ];

        if ($this->name) {
            $data['name'] = $this->name;
        }

        if ($this->delayedOption) {
            $data['delayed_option'] = $this->delayedOption;
        }

        if (! empty($this->attachments)) {
            $data['ios_attachments'] = $this->attachments;
            $data['big_picture'] = reset($this->attachments) ?: null;
        }

        if (! empty($this->externalUserIds)) {
            $data['include_external_user_ids'] = $this->externalUserIds;
        }

        if (! empty($this->segments)) {
            $data['included_segments'] = $this->segments;
        }

        return $data;
    }
}
