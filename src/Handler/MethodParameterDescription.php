<?php

namespace Messaging\Handler;

use Messaging\Support\MessageBuilder;

/**
 * Interface MethodParameterDescription
 * @package Messaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface MethodParameterDescription
{
    /**
     * @param MessageBuilder $messageBuilder
     * @return MessageBuilder
     */
    public function constructFrom(MessageBuilder $messageBuilder) : MessageBuilder;
}