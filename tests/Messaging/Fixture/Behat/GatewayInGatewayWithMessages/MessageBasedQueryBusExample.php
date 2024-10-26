<?php

namespace Test\Ecotone\Messaging\Fixture\Behat\GatewayInGatewayWithMessages;

use Ecotone\Messaging\Attribute\MessageGateway;
use Ecotone\Messaging\Attribute\Parameter\Header;
use Ecotone\Messaging\Attribute\Parameter\Payload;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Modelling\Config\MessageBusChannel;

/**
 * licence Apache-2.0
 */
interface MessageBasedQueryBusExample
{
    #[MessageGateway(MessageBusChannel::QUERY_CHANNEL_NAME_BY_NAME)]
    public function convertAndSend(#[Header(MessageBusChannel::QUERY_CHANNEL_NAME_BY_NAME)] string $name, #[Header(MessageHeaders::CONTENT_TYPE)] string $dataMediaType, #[Payload] $data): Message;
}
