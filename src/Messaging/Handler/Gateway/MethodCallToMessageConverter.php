<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Gateway;

use SimplyCodedSoftware\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadBuilder;
use SimplyCodedSoftware\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadConverter;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Support\Assert;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;

/**
 * Class MethodCallToMessageConverter
 * @package SimplyCodedSoftware\Messaging\Handler\Gateway\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MethodCallToMessageConverter
{
    /**
     * @var InterfaceToCall
     */
    private $interfaceToCall;
    /**
     * @var array|GatewayParameterConverter[]
     */
    private $methodArgumentConverters;

    /**
     * MethodCallToMessageConverter constructor.
     * @param InterfaceToCall $interfaceToCall
     * @param array|GatewayParameterConverter[] $methodArgumentConverters
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function __construct(InterfaceToCall $interfaceToCall, array $methodArgumentConverters)
    {
        $this->initialize($interfaceToCall, $methodArgumentConverters);
    }

    /**
     * @param MessageBuilder $messageBuilder
     * @param array|MethodArgument[] $methodArguments
     * @return MessageBuilder
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function convertFor(MessageBuilder $messageBuilder, array $methodArguments) : MessageBuilder
    {
        Assert::allInstanceOfType($methodArguments, MethodArgument::class);

        foreach ($methodArguments as $methodArgument) {
            foreach ($this->methodArgumentConverters as $methodParameterConverter) {
                if ($methodParameterConverter->isSupporting($methodArgument)) {
                    $messageBuilder = $methodParameterConverter->convertToMessage($methodArgument, $messageBuilder);
                }
            }
        }

        return $messageBuilder;
    }

    /**
     * @param MethodArgument[] $methodArguments
     * @return array
     */
    public function getHeaders(array $methodArguments) : array
    {
        $messageBuilder = MessageBuilder::withPayload("");
        foreach ($methodArguments as $methodArgument) {
            foreach ($this->methodArgumentConverters as $methodParameterConverter) {
                if ($methodParameterConverter->isSupporting($methodArgument) && !($methodParameterConverter instanceof GatewayPayloadConverter)) {
                    $messageBuilder = $methodParameterConverter->convertToMessage($methodArgument, $messageBuilder);
                }
            }
        }

        return $messageBuilder->getCurrentHeaders();
    }

    /**
     * @param MethodArgument[] $methodArguments
     * @return mixed
     */
    public function getPayloadArgument(array $methodArguments)
    {
        foreach ($methodArguments as $methodArgument) {
            foreach ($this->methodArgumentConverters as $methodParameterConverter) {
                if ($methodParameterConverter->isSupporting($methodArgument) && $methodParameterConverter instanceof GatewayPayloadConverter) {
                    return $methodArgument->value();
                }
            }
        }

        return "";
    }

    /**
     * @param InterfaceToCall $interfaceToCall
     * @param array|GatewayParameterConverter[] $methodArgumentConverters
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    private function initialize(InterfaceToCall $interfaceToCall, array $methodArgumentConverters) : void
    {
        Assert::allInstanceOfType($methodArgumentConverters, GatewayParameterConverter::class);

        $this->interfaceToCall = $interfaceToCall;

        if (empty($methodArgumentConverters) && $this->interfaceToCall->hasMoreThanOneParameter()) {
            throw InvalidArgumentException::create("You need to pass method argument converts for {$this->interfaceToCall}");
        }

        if (empty($methodArgumentConverters) && $this->interfaceToCall->hasSingleArgument()) {
            $methodArgumentConverters = [GatewayPayloadConverter::create($this->interfaceToCall->getFirstParameterName())];
        }

        $this->methodArgumentConverters = $methodArgumentConverters;
    }
}