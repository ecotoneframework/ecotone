<?php

namespace Messaging\Handler\Gateway\MethodParameterConverter;

use Messaging\Handler\Gateway\MethodArgument;
use Messaging\Handler\Gateway\MethodArgumentConverter;
use Messaging\Handler\Gateway\PayloadMethodArgumentConverter;
use Messaging\Support\MessageBuilder;

/**
 * Class PayloadMessageParameter
 * @package Messaging\Handler\Gateway\Gateway\MethodParameterConverter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class PayloadMethodArgumentMessageParameter implements PayloadMethodArgumentConverter
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
     * @return PayloadMethodArgumentMessageParameter
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
    public function createFrom(MethodArgument $methodArgument): MessageBuilder
    {
        return MessageBuilder::withPayload($methodArgument->value());
    }
}