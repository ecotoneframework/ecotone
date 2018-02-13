<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;

/**
 * Interface ParameterDefinition
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ParameterToMessageConverter
{
    /**
     * @param MethodArgument $methodArgument
     * @param MessageBuilder $messageBuilder
     * @return MessageBuilder
     */
    public function convertToMessage(MethodArgument $methodArgument, MessageBuilder $messageBuilder) : MessageBuilder;

    /**
     * @param MethodArgument $methodArgument
     * @return bool
     */
    public function isSupporting(MethodArgument $methodArgument) : bool;
}