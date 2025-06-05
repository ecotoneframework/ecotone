<?php

namespace Ecotone\Modelling\MessageHandling\Distribution;

use Ecotone\Messaging\Attribute\Parameter\Header;
use Ecotone\Messaging\Gateway\MessagingEntrypoint;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Modelling\Api\Distribution\DistributedBusHeader;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\Config\Routing\BusRoutingMap;
use Ecotone\Modelling\EventBus;

/**
 * licence Apache-2.0
 */
class DistributedMessageHandler
{
    public function __construct(
        private BusRoutingMap $distributedEventHandlerRoutingKeys,
        private BusRoutingMap $distributedCommandHandlerRoutingKeys,
        private string $thisServiceName,
    ) {
    }

    public function handle(
        mixed               $payload,
        array               $metadata,
        #[Header(DistributedBusHeader::DISTRIBUTED_PAYLOAD_TYPE)]
        string              $payloadType,
        #[Header(DistributedBusHeader::DISTRIBUTED_ROUTING_KEY)]
        string              $routingKey,
        #[Header(MessageHeaders::CONTENT_TYPE)]
        string              $contentType,
        #[Header(DistributedBusHeader::DISTRIBUTED_TARGET_SERVICE_NAME)]
        ?string             $targetedServiceName,
        CommandBus          $commandBus,
        EventBus            $eventBus,
        MessagingEntrypoint $messagingEntrypoint
    ) {
        if ($payloadType === 'event') {
            if (! empty($this->distributedEventHandlerRoutingKeys->get($routingKey))) {
                $eventBus->publishWithRouting($routingKey, $payload, $contentType, $metadata);
            }
        } elseif ($payloadType === 'command') {
            if (empty($this->distributedCommandHandlerRoutingKeys->get($routingKey))) {
                throw RoutingKeyIsNotDistributed::create('There is no Distributed Command Handler registered with routing key: ' . $routingKey);
            }

            if ($targetedServiceName !== null && $targetedServiceName !== $this->thisServiceName) {
                throw InvalidArgumentException::create("Received command message which targets {$targetedServiceName} Service, but consuming in {$this->thisServiceName}. Message was wrongly distributed.");
            }

            $commandBus->sendWithRouting($routingKey, $payload, $contentType, $metadata);
        } elseif ($payloadType === 'message') {
            if ($targetedServiceName !== null && $targetedServiceName !== $this->thisServiceName) {
                throw InvalidArgumentException::create("Received command message which targets {$targetedServiceName} Service, but consuming in {$this->thisServiceName}. Message was wrongly distributed.");
            }

            $messagingEntrypoint->sendWithHeaders($payload, $metadata, $routingKey);
        } else {
            throw InvalidArgumentException::create("Trying to call distributed command handler for payload type {$payloadType} and allowed are event/command/message");
        }
    }
}
