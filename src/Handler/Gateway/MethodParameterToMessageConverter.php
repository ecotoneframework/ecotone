<?php

namespace SimplyCodedSoftware\Messaging\Handler\Gateway;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;

/**
 * Interface ParameterDefinition
 * @package SimplyCodedSoftware\Messaging\Handler\Gateway\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface MethodParameterToMessageConverter extends NamedParameter
{
    /**
     * @param MethodArgument $methodArgument
     * @param MessageBuilder $messageBuilder
     * @return MessageBuilder
     */
    public function convertToMessage(MethodArgument $methodArgument, MessageBuilder $messageBuilder) : MessageBuilder;
}