<?php


namespace Ecotone\Modelling\MessageHandling\Distribution;


use Ecotone\Messaging\Annotation\Parameter\Header;
use Ecotone\Messaging\Annotation\Parameter\Headers;
use Ecotone\Messaging\Annotation\Parameter\Payload;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\DistributeGateway;
use Ecotone\Modelling\EventBus;

class DistributedMessageHandler
{
    public function handle(
        #[Payload] $payload, #[Headers] array $metadata,
        #[Header(DistributeGateway::DISTRIBUTED_PAYLOAD_TYPE)] string $payloadType,
        #[Header(DistributeGateway::DISTRIBUTED_ROUTING_KEY)] string $routingKey,
        #[Header(MessageHeaders::CONTENT_TYPE)] string $contentType,
        CommandBus $commandBus,
        EventBus $eventBus
    )
    {
        if ($payloadType === "event") {
            $eventBus->publishWithRouting($routingKey, $payload, $contentType, $metadata);
        }elseif ($payloadType === "command") {
            $commandBus->sendWithRouting($routingKey, $payload, $contentType, $metadata);
        }else {
            throw InvalidArgumentException::create("Trying to call distributed command handler for payload type {$payloadType} and allowed are event/command");
        }
    }
}