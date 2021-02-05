<?php


namespace Ecotone\Modelling\MessageHandling\Distribution;


use Ecotone\Messaging\Attribute\Parameter\Header;
use Ecotone\Messaging\Attribute\Parameter\Headers;
use Ecotone\Messaging\Attribute\Parameter\Payload;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\Config\EventBusRouter;
use Ecotone\Modelling\DistributionEntrypoint;
use Ecotone\Modelling\EventBus;

class DistributedMessageHandler
{
    private array $distributedEventHandlerRoutingKeys;
    private array $distributedCommandHandlerRoutingKeys;

    public function __construct(array $distributedEventHandlerRoutingKeys, array $distributedCommandHandlerRoutingKeys)
    {
        $this->distributedEventHandlerRoutingKeys = $distributedEventHandlerRoutingKeys;
        $this->distributedCommandHandlerRoutingKeys = $distributedCommandHandlerRoutingKeys;
    }

    public function handle(
        $payload, array $metadata,
        string $payloadType,
        #[Header(DistributionEntrypoint::DISTRIBUTED_ROUTING_KEY)] string $routingKey,
        #[Header(MessageHeaders::CONTENT_TYPE)] string $contentType,
        CommandBus $commandBus,
        EventBus $eventBus
    )
    {
        if ($payloadType === "event") {
            if ($this->hasAnyListingHandlers($routingKey)) {
                $eventBus->publishWithRouting($routingKey, $payload, $contentType, $metadata);
            }
        }elseif ($payloadType === "command") {
            if (!in_array($routingKey, $this->distributedCommandHandlerRoutingKeys)) {
                throw RoutingKeyIsNotDistributed::create("Trying to run NOT distributed command handler with routing key " . $routingKey);
            }

            $commandBus->sendWithRouting($routingKey, $payload, $contentType, $metadata);
        }else {
            throw InvalidArgumentException::create("Trying to call distributed command handler for payload type {$payloadType} and allowed are event/command");
        }
    }

    private function hasAnyListingHandlers(string $routingKey): bool
    {
        if (!in_array($routingKey, $this->distributedEventHandlerRoutingKeys)) {
            foreach ($this->distributedEventHandlerRoutingKeys as $listenTo) {
                if (EventBusRouter::doesListenForRoutedName($listenTo, $routingKey)) {
                    return true;
                }
            }

            return false;
        }

        return true;
    }
}