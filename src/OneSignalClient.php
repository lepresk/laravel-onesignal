<?php

declare(strict_types=1);

namespace Lepresk\LaravelOnesignal;

use Illuminate\Contracts\Events\Dispatcher;
use Lepresk\LaravelOnesignal\Contracts\NotificationResponseInterface;
use Lepresk\LaravelOnesignal\Contracts\OneSignalClientInterface;
use Lepresk\LaravelOnesignal\Events\NotificationFailed;
use Lepresk\LaravelOnesignal\Events\NotificationSending;
use Lepresk\LaravelOnesignal\Events\NotificationSent;
use Lepresk\LaravelOnesignal\Exceptions\InvalidMessageException;
use Lepresk\LaravelOnesignal\Exceptions\NotificationFailedException;
use OneSignal\OneSignal;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use Throwable;

class OneSignalClient implements OneSignalClientInterface
{
    private const array LOG_LEVELS = [
        LogLevel::EMERGENCY,
        LogLevel::ALERT,
        LogLevel::CRITICAL,
        LogLevel::ERROR,
        LogLevel::WARNING,
        LogLevel::NOTICE,
        LogLevel::INFO,
        LogLevel::DEBUG,
    ];

    protected LoggerInterface $logger;

    protected ?Dispatcher $events;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        protected OneSignal $client,
        protected array $config,
        ?LoggerInterface $logger = null,
        ?Dispatcher $events = null
    ) {
        $this->logger = $logger ?? new NullLogger;
        $this->events = $events;
    }

    /**
     * {@inheritdoc}
     */
    public function send(PushMessage $message): NotificationResponseInterface
    {
        $this->applyDefaults($message);

        $data = $message->build();

        $this->validateMessage($data);

        $this->events?->dispatch(new NotificationSending($message, $data));

        $this->log($this->successLogLevel(), 'Sending OneSignal notification', [
            'app_id' => $this->getAppId(),
            'audience' => $this->summarizeAudience($data),
        ]);

        try {
            $response = $this->client->notifications()->add($data);
        } catch (Throwable $e) {
            $this->log(LogLevel::ERROR, 'OneSignal notification exception', [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
            ]);

            $this->events?->dispatch(new NotificationFailed($message, $e));

            if ($this->shouldThrow()) {
                throw new NotificationFailedException(
                    'Exception occurred while sending notification: '.$e->getMessage(),
                    [],
                    0,
                    $e
                );
            }

            return new NotificationResponse(['errors' => [$e->getMessage()]], 0);
        }

        $statusCode = (int) ($response['_status_code'] ?? 0);
        $notificationResponse = new NotificationResponse($response, $statusCode);

        if (! $notificationResponse->isSuccessful()) {
            $errors = $notificationResponse->getErrors();

            $this->log(LogLevel::ERROR, 'OneSignal notification failed', [
                'errors' => $this->summarizeErrors($errors),
                'status_code' => $statusCode,
            ]);

            $exception = new NotificationFailedException(
                'Failed to send OneSignal notification',
                $errors,
                $statusCode
            );

            $this->events?->dispatch(new NotificationFailed($message, $exception));

            if ($this->shouldThrow()) {
                throw $exception;
            }

            return $notificationResponse;
        }

        $this->log($this->successLogLevel(), 'OneSignal notification sent successfully', [
            'notification_id' => $notificationResponse->getNotificationId(),
            'recipients' => $notificationResponse->getRecipients(),
        ]);

        $this->events?->dispatch(new NotificationSent($message, $notificationResponse));

        return $notificationResponse;
    }

    /**
     * {@inheritdoc}
     */
    public function getAppId(): string
    {
        return (string) $this->config['app_id'];
    }

    /**
     * {@inheritdoc}
     */
    public function getAndroidChannelId(): ?string
    {
        $channelId = $this->config['android_channel_id'] ?? null;

        return $channelId === null || $channelId === '' ? null : (string) $channelId;
    }

    /**
     * Validate the message data before sending.
     *
     * @param array<string, mixed> $data
     *
     * @throws InvalidMessageException
     */
    protected function validateMessage(array $data): void
    {
        if (empty($data['contents'])) {
            throw new InvalidMessageException('Message contents are required');
        }

        $targetsUsers = ! empty($data['include_aliases'])
            || ! empty($data['include_subscription_ids']);

        $targetsAudience = ! empty($data['filters'])
            || ! empty($data['included_segments']);

        if (! $targetsUsers && ! $targetsAudience) {
            throw new InvalidMessageException(
                'At least one target audience must be specified (external user IDs, filters, or segments)'
            );
        }

        if ($targetsUsers && $targetsAudience) {
            throw new InvalidMessageException(
                'External user IDs cannot be combined with filters or segments in the same notification'
            );
        }
    }

    /**
     * Apply default configuration values to the message.
     */
    protected function applyDefaults(PushMessage $message): void
    {
        if ($message->getAndroidChannelId() === null) {
            $androidChannelId = $this->getAndroidChannelId();
            if ($androidChannelId !== null) {
                $message->setAndroidChannelId($androidChannelId);
            }
        }

        if ($message->getTTL() === null) {
            $defaultTtl = $this->config['defaults']['ttl'] ?? null;
            if ($defaultTtl !== null) {
                $message->setTTL((int) $defaultTtl);
            }
        }

        if ($message->getPriority() === null) {
            $defaultPriority = $this->config['defaults']['priority'] ?? null;
            if ($defaultPriority !== null) {
                $message->setPriority((int) $defaultPriority);
            }
        }
    }

    /**
     * Check if logging is enabled.
     */
    protected function shouldLog(): bool
    {
        return (bool) ($this->config['logging']['enabled'] ?? true);
    }

    /**
     * Check if failures should throw exceptions.
     */
    protected function shouldThrow(): bool
    {
        return (bool) ($this->config['throw_exceptions'] ?? true);
    }

    /**
     * Get the configured log level for non-error entries.
     */
    protected function successLogLevel(): string
    {
        $level = strtolower((string) ($this->config['logging']['level'] ?? LogLevel::INFO));

        return in_array($level, self::LOG_LEVELS, true) ? $level : LogLevel::INFO;
    }

    /**
     * Write a log entry when logging is enabled.
     *
     * @param array<string, mixed> $context
     */
    protected function log(string $level, string $message, array $context = []): void
    {
        if ($this->shouldLog()) {
            $this->logger->log($level, $message, $context);
        }
    }

    /**
     * Describe the targeted audience without exposing personal data.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    protected function summarizeAudience(array $data): array
    {
        return array_filter([
            'external_user_ids' => count($data['include_aliases']['external_id'] ?? []),
            'subscription_ids' => count($data['include_subscription_ids'] ?? []),
            'filters' => count($data['filters'] ?? []),
            'segments' => $data['included_segments'] ?? [],
        ]);
    }

    /**
     * Summarize OneSignal errors without logging identifiers they may contain.
     *
     * @param array<array-key, mixed> $errors
     *
     * @return array<array-key, mixed>
     */
    protected function summarizeErrors(array $errors): array
    {
        return array_map(
            fn (mixed $error): mixed => is_array($error) ? count($error).' item(s)' : $error,
            $errors
        );
    }
}
