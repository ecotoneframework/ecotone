<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\AsyncPublishing;

use Ecotone\Messaging\BatchMessage;
use Ecotone\Messaging\Config\ConfiguredMessagingSystem;
use Ecotone\Messaging\Future;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * licence Enterprise
 */
final class AsyncPublishingGateway
{
    public function __construct(
        private string $publisherReference,
        private bool $asyncPublishingEnabled,
        private ConfiguredMessagingSystem $configuredMessagingSystem,
        private AsyncPublishingRegistry $asyncPublishingRegistry,
    ) {
    }

    public function publish(Message $message): Future
    {
        if (! $this->asyncPublishingEnabled) {
            throw PublishingFailedException::publisherNotConfiguredForAsyncPublishing($this->publisherReference);
        }

        $payload = $message->getPayload();
        if ($payload instanceof BatchMessage && count($payload) === 0) {
            return DeliveryFuture::forPendingDeliveries([]);
        }

        $scopeWasActive = $this->asyncPublishingRegistry->isScopeActive();
        if (! $scopeWasActive) {
            $this->asyncPublishingRegistry->openScope();
        }

        try {
            $collectionPoint = $this->asyncPublishingRegistry->collectionPoint();

            $this->configuredMessagingSystem->getMessageChannelByName($this->publisherReference)->send(
                MessageBuilder::fromMessage($message)
                    ->removeHeader(MessageHeaders::REPLY_CHANNEL)
                    ->removeHeader(MessageHeaders::ROUTING_SLIP)
                    ->build()
            );

            $pendingDeliveries = $this->asyncPublishingRegistry->registeredSince($collectionPoint);
            if (! $scopeWasActive) {
                $this->asyncPublishingRegistry->markRegisteredSinceAsPublisherOwned($collectionPoint);
            }
        } finally {
            if (! $scopeWasActive) {
                $this->asyncPublishingRegistry->closeScope();
            }
        }

        if ($pendingDeliveries === []) {
            throw PublishingFailedException::publisherNotConfiguredForAsyncPublishing($this->publisherReference);
        }

        return DeliveryFuture::forPendingDeliveries($pendingDeliveries);
    }
}
