<?php

namespace Ecotone\Modelling;

use Ecotone\Messaging\Attribute\MessageGateway;
use Ecotone\Messaging\Attribute\Parameter\Header;
use Ecotone\Messaging\Attribute\Parameter\Headers;
use Ecotone\Messaging\Attribute\Parameter\Payload;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\MessageHeaders;
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
