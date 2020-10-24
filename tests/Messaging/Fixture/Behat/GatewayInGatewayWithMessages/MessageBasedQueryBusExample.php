<?php

namespace Test\Ecotone\Messaging\Fixture\Behat\GatewayInGatewayWithMessages;

use Ecotone\Messaging\Annotation\MessageGateway;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\Parameter\Header;
use Ecotone\Messaging\Annotation\Parameter\Payload;
use Ecotone\Messaging\Message;
use Ecotone\Modelling\QueryBus;
use Ecotone\Messaging\MessageHeaders;

interface MessageBasedQueryBusExample
{
    /**
     * @MessageGateway(requestChannel=QueryBus::CHANNEL_NAME_BY_NAME)
     */
    public function convertAndSend(#[Header(QueryBus::CHANNEL_NAME_BY_NAME)] string $name, #[Header(MessageHeaders::CONTENT_TYPE)] string $dataMediaType, #[Payload] $data) : Message;
}