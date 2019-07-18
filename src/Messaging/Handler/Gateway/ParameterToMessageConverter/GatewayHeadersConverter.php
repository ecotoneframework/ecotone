<?php

namespace SimplyCodedSoftware\Messaging\Handler\Gateway\ParameterToMessageConverter;

use SimplyCodedSoftware\Messaging\Handler\Gateway\GatewayParameterConverter;
use SimplyCodedSoftware\Messaging\Handler\MethodArgument;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;

/**
 * Class GatewayHeaderArrayConverter
 * @package SimplyCodedSoftware\Messaging\Handler\Gateway\ParameterToMessageConverter
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class GatewayHeadersConverter implements GatewayParameterConverter
{
    /**
     * @var string
     */
    private $parameterName;

    /**
     * HeaderMessageParameter constructor.
     * @param string $parameterName
     */
    private function __construct(string $parameterName)
    {
        $this->parameterName = $parameterName;
    }

    /**
     * @param string $parameterName
     * @return self
     */
    public static function create(string $parameterName) : self
    {
        return new self($parameterName);
    }

    /**
     * @inheritDoc
     */
    public function convertToMessage(MethodArgument $methodArgument, MessageBuilder $messageBuilder): MessageBuilder
    {
        $headers = $methodArgument->value();

        if (!is_iterable($headers)) {
            throw InvalidArgumentException::create("@GatewayHeaderArray expect parameter to be iterable. Given non iterable value for parameter with name {$this->parameterName}");
        }

        foreach ($headers as $headerName => $headerValue) {
            if (!is_null($headerValue)) {
                $messageBuilder = $messageBuilder->setHeader($headerName, $headerValue);
            }
        }

        return $messageBuilder;
    }

    /**
     * @inheritDoc
     */
    public function isSupporting(MethodArgument $methodArgument): bool
    {
        return $this->parameterName == $methodArgument->getParameterName();
    }

}