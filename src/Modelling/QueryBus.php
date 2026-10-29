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
interface QueryBus
{
    #[MessageGateway(MessageBusChannel::QUERY_CHANNEL_NAME_BY_OBJECT)]
    public function send(
        #[Payload] object                                     $query,
        #[Headers] array                                      $metadata = [],
        #[Header(MessageHeaders::REPLY_CONTENT_TYPE)] ?string $expectedReturnedMediaType = null
    ): mixed;

    #[MessageGateway(MessageBusChannel::QUERY_CHANNEL_NAME_BY_NAME)]
    public function sendWithRouting(
        #[Header(MessageBusChannel::QUERY_CHANNEL_NAME_BY_NAME)] string $routingKey,
        #[Payload] mixed                                                $query = [],
        #[Header(MessageHeaders::CONTENT_TYPE)] string                  $queryMediaType = MediaType::APPLICATION_X_PHP,
        #[Headers] array                                                $metadata = [],
        #[Header(MessageHeaders::REPLY_CONTENT_TYPE)] ?string           $expectedReturnedMediaType = null
    ): mixed;
}
