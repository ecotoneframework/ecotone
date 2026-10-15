<?php

namespace Test\Ecotone\Messaging\Fixture\Behat\GatewayInGatewayWithMessages;

use Ecotone\Api\Attribute\Header;
use Ecotone\Api\Attribute\MessageGateway;
use Ecotone\Api\Attribute\Payload;
use Ecotone\Api\Messaging\Message;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\Modelling\Config\MessageBusChannel;

/**
 * licence Apache-2.0
 */
interface MessageBasedQueryBusExample
{
    #[MessageGateway(MessageBusChannel::QUERY_CHANNEL_NAME_BY_NAME)]
    public function convertAndSend(#[Header(MessageBusChannel::QUERY_CHANNEL_NAME_BY_NAME)] string $name, #[Header(MessageHeaders::CONTENT_TYPE)] string $dataMediaType, #[Payload] $data): Message;
}
