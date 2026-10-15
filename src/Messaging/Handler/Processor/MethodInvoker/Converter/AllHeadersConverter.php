<?php

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter;

use Ecotone\Api\Messaging\Message;
use Ecotone\Messaging\Handler\ParameterConverter;

/**
 * Class AllHeadersConverter
 * @package Ecotone\Messaging\Handler\Processor\MethodInvoker
 * @author  Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class AllHeadersConverter implements ParameterConverter
{
    /**
     * @inheritDoc
     */
    public function getArgumentFrom(Message $message): array
    {
        return $message->getHeaders()->headers();
    }
}
