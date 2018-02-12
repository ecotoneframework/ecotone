<?php

namespace SimplyCodedSoftware\Messaging\Handler\Gateway\ParameterToMessageConverter;

use SimplyCodedSoftware\Messaging\Handler\Gateway\MethodArgument;
use SimplyCodedSoftware\Messaging\Handler\Gateway\ParameterToMessageConverter;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;

/**
 * Class OnlyPayloadMessageParameterConverter
 * @package SimplyCodedSoftware\Messaging\Handler\Gateway\Gateway\MethodParameterConverter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class OnlyPayloadToMessageConverter implements ParameterToMessageConverter
{
    /**
     * @inheritDoc
     */
    public function isSupporting(MethodArgument $methodArgument): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function convertToMessage(MethodArgument $methodArgument, MessageBuilder $messageBuilder): MessageBuilder
    {
        return $messageBuilder->setPayload($methodArgument->value());
    }
}