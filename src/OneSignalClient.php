<?php

declare(strict_types=1);

namespace Lepresk\LaravelOnesignal;

use Illuminate\Contracts\Events\Dispatcher;
use Lepresk\LaravelOnesignal\Contracts\NotificationResponseInterface;
use Lepresk\LaravelOnesignal\Contracts\OneSignalClientInterface;
use Lepresk\LaravelOnesignal\Events\NotificationFailed;
use Lepresk\LaravelOnesignal\Events\NotificationSending;
use Lepresk\LaravelOnesignal\Events\NotificationSent;
use Lepresk\LaravelOnesignal\Exceptions\NotificationFailedException;
use OneSignal\OneSignal;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

class OneSignalClient implements OneSignalClientInterface
{
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

        if ($this->shouldLog()) {
            $this->logger->info('Sending OneSignal notification', [
                'app_id' => $this->getAppId(),
                'data' => $data,
            ]);
        }

        try {
            $response = $this->client->notifications()->add($data);
            $statusCode = $response['_status_code'] ?? 0;

            $notificationResponse = new NotificationResponse($response, $statusCode);

            if (! $notificationResponse->isSuccessful()) {
                $errors = $notificationResponse->getErrors();

                if ($this->shouldLog()) {
                    $this->logger->error('OneSignal notification failed', [
                        'errors' => $errors,
                        'status_code' => $statusCode,
                    ]);
                }

                if ($this->config['throw_exceptions'] ?? true) {
                    throw new NotificationFailedException(
                        'Failed to send OneSignal notification',
                        $errors,
                        $statusCode
                    );
                }
            }

            if ($this->shouldLog() && $notificationResponse->isSuccessful()) {
                $this->logger->info('OneSignal notification sent successfully', [
                    'notification_id' => $notificationResponse->getNotificationId(),
                    'recipients' => $notificationResponse->getRecipients(),
                ]);
            }

            if ($notificationResponse->isSuccessful()) {
                $this->events?->dispatch(new NotificationSent($message, $notificationResponse));
            }

            return $notificationResponse;
        } catch (NotificationFailedException $e) {
            $this->events?->dispatch(new NotificationFailed($message, $e));

            throw $e;
        } catch (Throwable $e) {
            if ($this->shouldLog()) {
                $this->logger->error('OneSignal notification exception', [
                    'message' => $e->getMessage(),
                    'exception' => get_class($e),
                ]);
            }

            $this->events?->dispatch(new NotificationFailed($message, $e));

            if ($this->config['throw_exceptions'] ?? true) {
                throw new NotificationFailedException(
                    'Exception occurred while sending notification: '.$e->getMessage(),
                    [],
                    0,
                    $e
                );
            }

            return new NotificationResponse(['errors' => [$e->getMessage()]], 0);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAppId(): string
    {
        return $this->config['app_id'];
    }

    /**
     * {@inheritdoc}
     */
    public function getAndroidChannelId(): ?string
    {
        return $this->config['android_channel_id'] ?? null;
    }

    /**
     * Validate the message data before sending.
     *
     * @param array<string, mixed> $data
     *
     * @throws \Lepresk\LaravelOnesignal\Exceptions\InvalidMessageException
     */
    protected function validateMessage(array $data): void
    {
        if (empty($data['contents'])) {
            throw new Exceptions\InvalidMessageException('Message contents are required');
        }

        $hasTargetAudience = ! empty($data['include_external_user_ids'])
            || ! empty($data['include_player_ids'])
            || ! empty($data['filters'])
            || ! empty($data['included_segments']);

        if (! $hasTargetAudience) {
            throw new Exceptions\InvalidMessageException(
                'At least one target audience must be specified (external user IDs, player IDs, filters, or segments)'
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
            if ($androidChannelId !== null && $androidChannelId !== '') {
                $message->setAndroidChannelId($androidChannelId);
            }
        }

        if ($message->getTTL() === null) {
            $defaultTtl = $this->config['defaults']['ttl'] ?? null;
            if ($defaultTtl !== null) {
                $message->setTTL($defaultTtl);
            }
        }

        if ($message->getPriority() === null) {
            $defaultPriority = $this->config['defaults']['priority'] ?? null;
            if ($defaultPriority !== null) {
                $message->setPriority($defaultPriority);
            }
        }
    }

    /**
     * Check if logging is enabled.
     */
    protected function shouldLog(): bool
    {
        return $this->config['logging']['enabled'] ?? true;
    }
}
