<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Gateway;

use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadConverter;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadExpressionConverter;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\MethodArgument;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * Class MethodCallToMessageConverter
 * @package Ecotone\Messaging\Handler\Gateway\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MethodCallToMessageConverter
{
    private ?\Ecotone\Messaging\Handler\InterfaceToCall $interfaceToCall;
    private ?array $methodArgumentConverters;

    /**
     * MethodCallToMessageConverter constructor.
     * @param InterfaceToCall $interfaceToCall
     * @param array|GatewayParameterConverter[] $methodArgumentConverters
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function __construct(InterfaceToCall $interfaceToCall, array $methodArgumentConverters)
    {
        $this->initialize($interfaceToCall, $methodArgumentConverters);
    }

    /**
     * @param MethodArgument[] $methodArguments
     */
    public function convertFor(MessageBuilder $messageBuilder, array $methodArguments) : MessageBuilder
    {
        Assert::allInstanceOfType($methodArguments, MethodArgument::class);

        foreach ($this->methodArgumentConverters as $methodParameterConverter) {
            if (empty($methodArguments) && $methodParameterConverter->isSupporting(null) && !$this->isPayloadConverter($methodParameterConverter)) {
                $messageBuilder = $methodParameterConverter->convertToMessage(null, $messageBuilder);
                break;
            }

            foreach ($methodArguments as $methodArgument) {
                if ($methodParameterConverter->isSupporting($methodArgument) && !$this->isPayloadConverter($methodParameterConverter)) {
                    $messageBuilder = $methodParameterConverter->convertToMessage($methodArgument, $messageBuilder);
                    break;
                }
            }
        }

        return $messageBuilder;
    }

    /**
     * @param MethodArgument[] $methodArguments
     * @return MessageBuilder
     */
    public function getMessageBuilderUsingPayloadConverter(array $methodArguments) : MessageBuilder
    {
        $defaultBuilder = MessageBuilder::withPayload("");
        foreach ($methodArguments as $methodArgument) {
            foreach ($this->methodArgumentConverters as $methodParameterConverter) {
                if ($methodParameterConverter->isSupporting($methodArgument) && $this->isPayloadConverter($methodParameterConverter)) {
                    return $methodParameterConverter->convertToMessage($methodArgument, $defaultBuilder);
                }
            }
        }

        return $defaultBuilder;
    }

    /**
     * @param InterfaceToCall $interfaceToCall
     * @param array|GatewayParameterConverter[] $methodArgumentConverters
     * @throws InvalidArgumentException
     * @throws \Ecotone\Messaging\MessagingException
     */
    private function initialize(InterfaceToCall $interfaceToCall, array $methodArgumentConverters) : void
    {
        Assert::allInstanceOfType($methodArgumentConverters, GatewayParameterConverter::class);

        $this->interfaceToCall = $interfaceToCall;

        if (empty($methodArgumentConverters) && $this->interfaceToCall->hasMoreThanOneParameter()) {
            throw InvalidArgumentException::create("You need to pass method argument converts for {$this->interfaceToCall}");
        }

        if (empty($methodArgumentConverters) && $this->interfaceToCall->hasSingleParameter()) {
            $methodArgumentConverters = [GatewayPayloadConverter::create($this->interfaceToCall->getFirstParameterName())];
        }

        $this->methodArgumentConverters = $methodArgumentConverters;
    }

    /**
     * @param GatewayParameterConverter $methodParameterConverter
     * @return bool
     */
    private function isPayloadConverter(GatewayParameterConverter $methodParameterConverter): bool
    {
        return ($methodParameterConverter instanceof GatewayPayloadConverter || $methodParameterConverter instanceof GatewayPayloadExpressionConverter);
    }
}