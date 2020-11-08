<?php

namespace Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter;

use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\Gateway\GatewayParameterConverter;
use Ecotone\Messaging\Handler\MethodArgument;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * Class GatewayHeaderArrayConverter
 * @package Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class GatewayHeadersConverter implements GatewayParameterConverter
{
    private string $parameterName;

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
    public function convertToMessage(?MethodArgument $methodArgument, MessageBuilder $messageBuilder): MessageBuilder
    {
        Assert::notNull($methodArgument, "Gateway header converter can only be called with method argument");

        $headers = $methodArgument->value();

        if (!TypeDescriptor::createFromVariable($headers)->isIterable()) {
            throw InvalidArgumentException::create("Gateway @Headers expect parameter to be iterable. Given non iterable value for parameter with name {$this->parameterName}");
        }

        foreach ($headers as $headerName => $headerValue) {
            if (in_array($headerName, [MessageHeaders::ROUTING_SLIP])) {
                continue;
            }
            if ($headerName === MessageHeaders::CONTENT_TYPE) {
                $messagePayloadType = TypeDescriptor::createFromVariable($messageBuilder->getPayload());
                $mediaType = MediaType::parseMediaType($headerValue);
                if (!$messagePayloadType->isScalar() && !$mediaType->isCompatibleWith(MediaType::createApplicationXPHP())) {
                    continue;
                }

                if ($mediaType->hasTypeParameter() && !$messagePayloadType->isCompatibleWith($mediaType->getTypeParameter())) {
                    continue;
                }
            }
            if (!is_null($headerValue)) {
                $messageBuilder = $messageBuilder->setHeader($headerName, $headerValue);
            }
        }

        return $messageBuilder;
    }

    /**
     * @inheritDoc
     */
    public function isSupporting(?MethodArgument $methodArgument): bool
    {
        return $methodArgument && ($this->parameterName == $methodArgument->getParameterName());
    }

}