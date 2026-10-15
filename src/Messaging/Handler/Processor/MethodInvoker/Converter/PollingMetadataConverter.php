<?php

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter;

use Ecotone\Api\ExtensionObject\PollingMetadata;
use Ecotone\Api\Messaging\Message;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\Messaging\Handler\ParameterConverter;

/**
 * licence Apache-2.0
 */
class PollingMetadataConverter implements ParameterConverter
{
    public function getArgumentFrom(Message $message): ?PollingMetadata
    {
        if ($message->getHeaders()->containsKey(MessageHeaders::CONSUMER_POLLING_METADATA)) {
            return $message->getHeaders()->get(MessageHeaders::CONSUMER_POLLING_METADATA);
        } else {
            return null;
        }
    }
}
