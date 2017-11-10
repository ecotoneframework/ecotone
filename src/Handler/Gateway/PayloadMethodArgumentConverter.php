<?php

namespace Messaging\Handler\Gateway;

use Messaging\Support\MessageBuilder;

/**
 * Interface MessageBuilderFromPayloadMethodParameter
 * @package Messaging\Handler\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface PayloadMethodArgumentConverter extends NamedParameter
{
    /**
     * @param MethodArgument $methodArgument
     * @return MessageBuilder
     */
    public function createFrom(MethodArgument $methodArgument) : MessageBuilder;
}