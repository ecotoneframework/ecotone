<?php

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter;

use Ecotone\Messaging\Handler\ParameterConverter;
use Ecotone\Messaging\Message;

/**
 * Class AllHeadersConverter
 * @package Ecotone\Messaging\Handler\Processor\MethodInvoker
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
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
