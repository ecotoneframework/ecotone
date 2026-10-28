<?php

namespace Ecotone\Modelling\MessageHandling\Distribution;

use function array_flip;
use function array_intersect_key;

use Ecotone\Api\Attribute\Header;
use Ecotone\Api\Gateway\CommandBus;
use Ecotone\Api\Gateway\DistributedBusHeader;
use Ecotone\Api\Gateway\EventBus;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\Messaging\Gateway\MessagingEntrypointService;
use Ecotone\Messaging\Support\FrameworkHeaders;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Modelling\Config\Routing\BusRoutingMap;

/**
 * licence Apache-2.0
 */
class DistributedMessageHandler
{
    public const REPLY_CHANNEL = 'ecotone.distributed.reply';
    public const REPLY_ROUTING_CHANNEL = 'ecotone.distributed.reply.route';

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
        #[Header(DistributedBusHeader::DISTRIBUTED_REPLY_ROUTING_KEY)]
        ?string             $replyRoutingKey,
        CommandBus          $commandBus,
        EventBus            $eventBus,
        MessagingEntrypointService $messagingEntrypoint
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

            $messagingEntrypoint->sendWithHeaders($payload, $metadata, $routingKey, $replyRoutingKey === null ? null : self::REPLY_CHANNEL);
        } else {
            throw InvalidArgumentException::create("Trying to call distributed command handler for payload type {$payloadType} and allowed are event/command/message");
        }
    }

    public function reply(
        mixed $result,
        array $metadata,
        #[Header(DistributedBusHeader::DISTRIBUTED_REPLY_ROUTING_KEY)]
        string $replyRoutingKey,
        MessagingEntrypointService $messagingEntrypoint
    ): void {
        $messagingEntrypoint->sendWithHeaders($result, $this->replyHeaders($metadata, $replyRoutingKey), self::REPLY_ROUTING_CHANNEL);
    }

    private function replyHeaders(array $resultMetadata, string $replyRoutingKey): array
    {
        return [
            ...FrameworkHeaders::unsetAllFrameworkHeaders($resultMetadata),
            ...array_intersect_key($resultMetadata, array_flip([MessageHeaders::TRACEPARENT, MessageHeaders::TRACESTATE, MessageHeaders::TRACING_ID])),
            MessageHeaders::MESSAGE_CORRELATION_ID => $resultMetadata[MessageHeaders::MESSAGE_CORRELATION_ID],
            MessageHeaders::PARENT_MESSAGE_ID => $resultMetadata[MessageHeaders::MESSAGE_ID],
            MessageHeaders::ROUTING_SLIP => DistributedBusHeader::DISTRIBUTED_ROUTING_SLIP_VALUE,
            DistributedBusHeader::DISTRIBUTED_PAYLOAD_TYPE => 'message',
            DistributedBusHeader::DISTRIBUTED_ROUTING_KEY => $replyRoutingKey,
            DistributedBusHeader::DISTRIBUTED_SOURCE_SERVICE_NAME => $this->thisServiceName,
            DistributedBusHeader::DISTRIBUTED_TARGET_SERVICE_NAME => $resultMetadata[DistributedBusHeader::DISTRIBUTED_SOURCE_SERVICE_NAME] ?? null,
        ];
    }
}
