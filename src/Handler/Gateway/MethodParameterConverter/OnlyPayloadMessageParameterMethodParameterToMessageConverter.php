<?php

namespace SimplyCodedSoftware\Messaging\Handler\Gateway\MethodParameterConverter;

use SimplyCodedSoftware\Messaging\Handler\Gateway\MethodArgument;
use SimplyCodedSoftware\Messaging\Handler\Gateway\MethodParameterToMessageConverter;
use SimplyCodedSoftware\Messaging\Handler\Gateway\PayloadMethodArgumentConverter;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;

/**
 * Class OnlyPayloadMessageParameterConverter
 * @package SimplyCodedSoftware\Messaging\Handler\Gateway\Gateway\MethodParameterConverter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class OnlyPayloadMessageParameterMethodParameterToMessageConverter implements MethodParameterToMessageConverter
{
    /**
     * @inheritDoc
     */
    public function hasParameterNameAs(MethodArgument $methodArgument): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function hasParameterName(string $parameterName): bool
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