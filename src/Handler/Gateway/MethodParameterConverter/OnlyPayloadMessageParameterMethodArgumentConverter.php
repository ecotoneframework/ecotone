<?php

namespace Messaging\Handler\Gateway\MethodParameterConverter;

use Messaging\Handler\Gateway\MethodArgument;
use Messaging\Handler\Gateway\MethodArgumentConverter;
use Messaging\Handler\Gateway\PayloadMethodArgumentConverter;
use Messaging\Support\MessageBuilder;

/**
 * Class OnlyPayloadMessageParameterConverter
 * @package Messaging\Handler\Gateway\Gateway\MethodParameterConverter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class OnlyPayloadMessageParameterMethodArgumentConverter implements MethodArgumentConverter
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