<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\ParameterToMessageConverter;

use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\MethodArgument;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\ParameterToMessageConverter;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;

/**
 * Class OnlyPayloadMessageParameterConverter
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\Gateway\MethodParameterConverter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class OnlyPayloadConverter implements ParameterToMessageConverter
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