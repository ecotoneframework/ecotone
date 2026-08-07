<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\AsyncPublishing;

use Ecotone\Messaging\BatchMessage;
use Ecotone\Messaging\Channel\AsyncPublishing\AsyncPublishingRegistry;
use Ecotone\Messaging\Channel\AsyncPublishing\PublishingFailedException;
use Ecotone\Messaging\Channel\BatchSupportingMessageChannel;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\PollableChannel;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * licence Apache-2.0
 */
final class InMemoryAsyncPublishingChannel implements PollableChannel, BatchSupportingMessageChannel
{
    /** @var Message[] */
    private array $queue = [];

    private ?string $deliveryFailureReason = null;

    private ?string $failingPayloadFragment = null;

    public function __construct(
        private string $channelName,
        private AsyncPublishingRegistry $asyncPublishingRegistry,
        private OperationsLog $operationsLog,
    ) {
    }

    public function send(Message $message): void
    {
        $payload = $message->getPayload();

        if ($payload instanceof BatchMessage) {
            $this->operationsLog->log(sprintf('published batch of %d messages to broker', count($payload)));
            foreach ($payload->getEntries() as $entry) {
                $this->queue[] = MessageBuilder::withPayload($entry['payload'])
                    ->setMultipleHeaders($entry['headers'])
                    ->build();
            }
        } else {
            $this->operationsLog->log('published message to broker');
            $this->queue[] = $message;
        }

        $pendingDelivery = new InMemoryPendingDelivery(
            $message,
            $this->resolveFailureReason($message),
            $this->operationsLog,
            $this->channelName,
            failedMessages: $this->resolveFailedMessages($message),
        );

        if (! $this->asyncPublishingRegistry->isScopeActive()) {
            $deliveryResult = $pendingDelivery->awaitDelivery();
            if (! $deliveryResult->isSuccessful()) {
                throw PublishingFailedException::withFailedDeliveries($deliveryResult->getFailedDeliveries());
            }

            return;
        }

        $this->asyncPublishingRegistry->register($this->channelName, $pendingDelivery);
    }

    public function receive(): ?Message
    {
        return array_shift($this->queue) ?: null;
    }

    public function receiveWithTimeout(PollingMetadata $pollingMetadata): ?Message
    {
        return $this->receive();
    }

    public function onConsumerStop(): void
    {
    }

    public function supportsBatchMessages(): bool
    {
        return true;
    }

    public function failDeliveriesWith(string $failureReason): void
    {
        $this->deliveryFailureReason = $failureReason;
    }

    public function failDeliveriesContaining(string $payloadFragment, string $failureReason): void
    {
        $this->failingPayloadFragment = $payloadFragment;
        $this->deliveryFailureReason = $failureReason;
    }

    private function resolveFailureReason(Message $message): ?string
    {
        if ($this->failingPayloadFragment === null) {
            return $this->deliveryFailureReason;
        }

        return $this->resolveFailedMessages($message) === [] ? null : $this->deliveryFailureReason;
    }

    /**
     * @return Message[]
     */
    private function resolveFailedMessages(Message $message): array
    {
        if ($this->failingPayloadFragment === null) {
            return [];
        }

        $payload = $message->getPayload();
        if (! $payload instanceof BatchMessage) {
            return $this->matchesFailingFragment($payload) ? [$message] : [];
        }

        $failedMessages = [];
        foreach ($payload->getEntries() as $entry) {
            if ($this->matchesFailingFragment($entry['payload'])) {
                $failedMessages[] = MessageBuilder::withPayload($entry['payload'])
                    ->setMultipleHeaders($entry['headers'])
                    ->build();
            }
        }

        return $failedMessages;
    }

    private function matchesFailingFragment(mixed $payload): bool
    {
        $payloadAsString = is_string($payload) ? $payload : json_encode($payload);

        return is_string($payloadAsString) && str_contains($payloadAsString, $this->failingPayloadFragment);
    }
}
