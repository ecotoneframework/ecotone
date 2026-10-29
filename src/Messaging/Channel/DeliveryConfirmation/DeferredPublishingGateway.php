<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\DeliveryConfirmation;

use Ecotone\Messaging\BatchMessage;
use Ecotone\Messaging\Config\ConfiguredMessagingSystem;
use Ecotone\Messaging\Future;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * licence Enterprise
 */
final class DeferredPublishingGateway
{
    public function __construct(
        private string $publisherReference,
        private bool $nonBlockingConfirmationEnabled,
        private ConfiguredMessagingSystem $configuredMessagingSystem,
        private PendingDeliveryRegistry $pendingDeliveryRegistry,
    ) {
    }

    public function publish(Message $message): Future
    {
        if (! $this->nonBlockingConfirmationEnabled) {
            throw PublishingFailedException::publisherNotConfiguredForDeferredPublishing($this->publisherReference);
        }

        $payload = $message->getPayload();
        if ($payload instanceof BatchMessage && count($payload) === 0) {
            return DeliveryFuture::forPendingDeliveries([]);
        }

        $scopeWasActive = $this->pendingDeliveryRegistry->isScopeActive();
        if (! $scopeWasActive) {
            $this->pendingDeliveryRegistry->openScope();
        }

        try {
            $collectionPoint = $this->pendingDeliveryRegistry->collectionPoint();

            $this->configuredMessagingSystem->getMessageChannelByName($this->publisherReference)->send(
                MessageBuilder::fromMessage($message)
                    ->removeHeader(MessageHeaders::REPLY_CHANNEL)
                    ->removeHeader(MessageHeaders::ROUTING_SLIP)
                    ->build()
            );

            $pendingDeliveries = $this->pendingDeliveryRegistry->registeredSince($collectionPoint);
            if (! $scopeWasActive) {
                $this->pendingDeliveryRegistry->markRegisteredSinceAsPublisherOwned($collectionPoint);
            }
        } finally {
            if (! $scopeWasActive) {
                $this->pendingDeliveryRegistry->closeScope();
            }
        }

        if ($pendingDeliveries === []) {
            throw PublishingFailedException::publisherNotConfiguredForDeferredPublishing($this->publisherReference);
        }

        return DeliveryFuture::forPendingDeliveries($pendingDeliveries);
    }
}
