<?php

namespace Messaging\Handler\Gateway;
use Messaging\Support\MessageBuilder;

/**
 * Interface ParameterDefinition
 * @package Messaging\Handler\Gateway\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface MethodArgumentConverter extends NamedParameter
{
    /**
     * @param mixed $argumentValue
     * @param MessageBuilder $messageBuilder
     * @return MessageBuilder
     */
    public function convertToMessage($argumentValue, MessageBuilder $messageBuilder) : MessageBuilder;
}