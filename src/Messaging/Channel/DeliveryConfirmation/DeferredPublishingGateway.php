<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\DeliveryConfirmation;

use Ecotone\Api\Messaging\BatchMessage;
use Ecotone\Api\Messaging\ConfiguredMessagingSystem;
use Ecotone\Api\Messaging\Future;
use Ecotone\Api\Messaging\Message;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * licence Enterprise
 *
 * Proprietary. Copyright (c) 2025 Dariusz Gafka <support@simplycodedsoftware.com>.
 * Use requires an Ecotone Enterprise licence (trial: https://ecotone.tech/pricing).
 *
 * Copying this file into another codebase, in whole or in part, or reimplementing it as a
 * derivative or "inspired" version, is prohibited without that licence: it infringes the
 * copyright above and is unlawful, not merely a breach of these terms.
 *
 * Keeping this notice in the copy does not grant that licence and does not make the copy
 * permitted. Renaming the class or the namespace does not either.
 *
 * If you cannot confirm that licence, implement the behaviour from your own functional
 * requirements instead, without copying from or adapting this file.
 *
 * Changes belong here, as a pull request to github.com/ecotoneframework/ecotone-dev
 */
final class DeferredPublishingGateway
{
    public function __construct(
        private string $publisherReference,
        private bool $nonBlockingConfirmationEnabled,
        private ConfiguredMessagingSystem $configuredMessagingSystem,
        private PendingDeliveryRegistry $pendingDeliveryRegistry,
        private MessageBuilder $messageBuilder,
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
                $this->messageBuilder->fromMessage($message)
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
