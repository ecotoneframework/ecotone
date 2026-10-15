<?php

namespace Ecotone\Api\Gateway;

use Ecotone\Api\Attribute\Header;
use Ecotone\Api\Attribute\Headers;
use Ecotone\Api\Attribute\MessageGateway;
use Ecotone\Api\Attribute\Payload;
use Ecotone\Api\ExtensionObject\MediaType;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\Modelling\Config\MessageBusChannel;

/**
 * licence Apache-2.0
 */
interface EventBus
{
    #[MessageGateway(MessageBusChannel::EVENT_CHANNEL_NAME_BY_OBJECT)]
    public function publish(
        #[Payload] object $event,
        #[Headers] array  $metadata = []
    ): void;

    #[MessageGateway(MessageBusChannel::EVENT_CHANNEL_NAME_BY_NAME)]
    public function publishWithRouting(
        #[Header(MessageBusChannel::EVENT_CHANNEL_NAME_BY_NAME)] string $routingKey,
        #[Payload] mixed                                                $event = [],
        #[Header(MessageHeaders::CONTENT_TYPE)] string                  $eventMediaType = MediaType::APPLICATION_X_PHP,
        #[Headers] array                                                $metadata = []
    ): void;
}
