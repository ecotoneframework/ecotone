<?php

namespace Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter;

use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\Gateway\GatewayParameterConverter;
use Ecotone\Messaging\Handler\MethodArgument;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Messaging\Support\MessageBuilder;

use function is_iterable;

/**
 * Class GatewayHeaderArrayConverter
 * @package Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter
 * @author  Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class GatewayHeadersConverter implements GatewayParameterConverter
{
    public function __construct(private string $parameterName)
    {
    }

    /**
     * @inheritDoc
     */
    public function convertToMessage(?MethodArgument $methodArgument, MessageBuilder $messageBuilder): MessageBuilder
    {
        Assert::notNull($methodArgument, 'Gateway header converter can only be called with method argument');

        $headers = $methodArgument->value();

        if (! is_iterable($headers)) {
            throw InvalidArgumentException::create("Gateway @Headers expect parameter to be iterable. Given non iterable value for parameter with name {$this->parameterName}");
        }

        foreach ($headers as $headerName => $headerValue) {
            /**
             * Do not propagate routing slip when calling higher level Gateways (Command, Query, Event Bus)
             * This is because they start new flows which should not be routed back to the original one
             */
            if ($headerName === MessageHeaders::ROUTING_SLIP) {
                continue;
            }
            if ($headerName === MessageHeaders::CONTENT_TYPE) {
                $messagePayloadType = Type::createFromVariable($messageBuilder->getPayload());
                $mediaType = MediaType::parseMediaType($headerValue);
                if (! $messagePayloadType->isScalar() && ! $mediaType->isCompatibleWith(MediaType::createApplicationXPHP())) {
                    continue;
                }

                if ($mediaType->hasTypeParameter() && ! $messagePayloadType->isCompatibleWith($mediaType->getTypeParameter())) {
                    continue;
                }
            }
            if (! is_null($headerValue)) {
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
        return $methodArgument && ($this->parameterName === $methodArgument->getParameterName());
    }
}
