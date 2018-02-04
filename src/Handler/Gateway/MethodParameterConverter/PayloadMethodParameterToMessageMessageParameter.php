<?php

namespace SimplyCodedSoftware\Messaging\Handler\Gateway\MethodParameterConverter;

use SimplyCodedSoftware\Messaging\Handler\Gateway\MethodArgument;
use SimplyCodedSoftware\Messaging\Handler\Gateway\MethodParameterToMessageConverter;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;

/**
 * Class PayloadMessageParameter
 * @package SimplyCodedSoftware\Messaging\Handler\Gateway\Gateway\MethodParameterConverter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class PayloadMethodParameterToMessageMessageParameter implements MethodParameterToMessageConverter
{
    /**
     * @var string
     */
    private $parameterName;

    /**
     * PayloadMessageParameter constructor.
     * @param string $parameterName
     */
    private function __construct(string $parameterName)
    {
        $this->parameterName = $parameterName;
    }

    /**
     * @param string $parameterName
     * @return PayloadMethodParameterToMessageMessageParameter
     */
    public static function create(string $parameterName) : self
    {
        return new self($parameterName);
    }

    /**
     * @inheritDoc
     */
    public function hasParameterNameAs(MethodArgument $methodArgument): bool
    {
        return $this->parameterName == $methodArgument->getParameterName();
    }

    /**
     * @inheritDoc
     */
    public function hasParameterName(string $parameterName): bool
    {
        return $this->parameterName == $parameterName;
    }

    /**
     * @inheritDoc
     */
    public function convertToMessage(MethodArgument $methodArgument, MessageBuilder $messageBuilder): MessageBuilder
    {
        return $messageBuilder->setPayload($methodArgument->value());
    }
}