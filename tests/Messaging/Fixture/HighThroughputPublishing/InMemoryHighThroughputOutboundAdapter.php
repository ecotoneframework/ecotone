<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\HighThroughputPublishing;

use Ecotone\Api\Attribute\Reference;
use Ecotone\Api\Messaging\BatchMessage;
use Ecotone\Api\Messaging\Message;
use Ecotone\Messaging\Channel\DeliveryConfirmation\PendingDeliveryRegistry;
use Ecotone\Test\StaticMessageBuilder;

/**
 * licence Apache-2.0
 */
final class InMemoryHighThroughputOutboundAdapter
{
    /** @var Message[] */
    private array $sentMessages = [];

    /** @var InMemoryPendingDelivery[] */
    private array $pendingDeliveries = [];

    private ?string $deliveryFailureReason = null;

    private ?string $failingPayloadFragment = null;

    private bool $registersPendingDeliveries = true;

    public function handle(Message $message, #[Reference] PendingDeliveryRegistry $pendingDeliveryRegistry): void
    {
        $this->sentMessages[] = $message;

        if (! $this->registersPendingDeliveries) {
            return;
        }

        $pendingDelivery = new InMemoryPendingDelivery($message, $this->resolveFailureReason($message), failedMessages: $this->resolveFailedMessages($message));
        $this->pendingDeliveries[] = $pendingDelivery;
        $pendingDeliveryRegistry->register(InMemoryHighThroughputPublisherModule::PUBLISHER_REFERENCE, $pendingDelivery);
    }

    /**
     * @return Message[]
     */
    public function getSentMessages(): array
    {
        return $this->sentMessages;
    }

    /**
     * @return mixed[]
     */
    public function getSentPayloads(): array
    {
        return array_map(fn (Message $message) => $message->getPayload(), $this->sentMessages);
    }

    public function awaitedDeliveriesCount(): int
    {
        return count(array_filter($this->pendingDeliveries, fn (InMemoryPendingDelivery $pendingDelivery) => $pendingDelivery->isAwaited()));
    }

    public function totalAwaitCalls(): int
    {
        return array_sum(array_map(fn (InMemoryPendingDelivery $pendingDelivery) => $pendingDelivery->awaitCalls(), $this->pendingDeliveries));
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
        foreach ($payload->entries as $entry) {
            if ($this->matchesFailingFragment($entry['payload'])) {
                $failedMessages[] = StaticMessageBuilder::withPayload($entry['payload'])
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

    public function actAsSynchronousPublisher(): void
    {
        $this->registersPendingDeliveries = false;
    }
}
