<?php

namespace Messaging\Handler\Gateway;
use Messaging\Support\MessageBuilder;

/**
 * Interface ParameterDefinition
 * @package Messaging\Handler\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface MethodArgumentConverter extends NamedParameter
{
    /**
     * @param MethodArgument $methodArgument
     * @param MessageBuilder $messageBuilder
     * @return MessageBuilder
     */
    public function convertToMessage(MethodArgument $methodArgument, MessageBuilder $messageBuilder) : MessageBuilder;
}