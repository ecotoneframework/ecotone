<?php

namespace Messaging\Handler\Gateway;

use Messaging\Handler\Gateway\MethodParameterConverter\DefaultPayloadArgumentConverter;
use Messaging\Handler\Gateway\MethodParameterConverter\OnlyPayloadMessageParameterMethodArgumentConverter;
use Messaging\Message;
use Messaging\Support\Assert;
use Messaging\Support\InvalidArgumentException;
use Messaging\Support\MessageBuilder;

/**
 * Class MethodCallToMessageConverter
 * @package Messaging\Handler\Gateway\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MethodCallToMessageConverter
{
    /**
     * @var InterfaceToCall
     */
    private $interfaceToCall;
    /**
     * @var PayloadMethodArgumentConverter
     */
    private $payloadConverter;
    /**
     * @var array|MethodArgumentConverter[]
     */
    private $methodArgumentConverters;

    /**
     * MethodCallToMessageConverter constructor.
     * @param string $interfaceToCall
     * @param string $methodName
     * @param PayloadMethodArgumentConverter|null $payloadMethodArgumentConverter
     * @param array|MethodArgumentConverter[] $methodArgumentConverters
     */
    public function __construct(string $interfaceToCall, string $methodName, ?PayloadMethodArgumentConverter $payloadMethodArgumentConverter, array $methodArgumentConverters)
    {
        $this->initialize($interfaceToCall, $methodName, $payloadMethodArgumentConverter, $methodArgumentConverters);
    }

    /**
     * @param array|mixed[] $methodArguments
     * @return Message
     */
    public function convertFor(array $methodArguments) : Message
    {
        Assert::allInstanceOfType($methodArguments, MethodArgument::class);
        $messageBuilder = null;

        if ($this->interfaceToCall->hasNoArguments()) {
            $messageBuilder = MessageBuilder::withPayload("empty");
        }

        foreach ($methodArguments as $methodArgument) {
            if ($this->payloadConverter->hasParameterNameAs($methodArgument)) {
                $messageBuilder = $this->payloadConverter->createFrom($methodArgument);
                break;
            }
        }

        foreach ($methodArguments as $methodArgument) {
            foreach ($this->methodArgumentConverters as $methodParameterConverter) {
                if ($methodArgument->getParameterName() == $methodParameterConverter->parameterName()) {
                    $messageBuilder = $methodParameterConverter->convertToMessage($methodArgument, $messageBuilder);
                    break;
                }
            }
        }

        return $messageBuilder
                ->build();
    }

    /**
     * @param string $interfaceToCall
     * @param string $methodName
     * @param PayloadMethodArgumentConverter|null $payloadMethodArgumentConverter
     * @param array|MethodArgumentConverter[] $methodArgumentConverters
     * @throws \Messaging\MessagingException
     */
    private function initialize(string $interfaceToCall, string $methodName, ?PayloadMethodArgumentConverter $payloadMethodArgumentConverter, array $methodArgumentConverters) : void
    {
        Assert::allInstanceOfType($methodArgumentConverters, MethodArgumentConverter::class);

        $this->interfaceToCall = InterfaceToCall::create($interfaceToCall, $methodName);
        $this->methodArgumentConverters = $methodArgumentConverters;
        $this->payloadConverter = $payloadMethodArgumentConverter;

        if (is_null($payloadMethodArgumentConverter) && $this->interfaceToCall->hasMoreThanOneParameter()) {
            throw InvalidArgumentException::create("You need to pass method argument converts for {$this->interfaceToCall}");
        }
        if (is_null($payloadMethodArgumentConverter) && !$this->interfaceToCall->hasNoArguments()) {
            $this->payloadConverter = new OnlyPayloadMessageParameterMethodArgumentConverter();
        }

        $methodArgumentConvertersPlusPayloadConverter = $methodArgumentConverters;
        $methodArgumentConvertersPlusPayloadConverter[] = $this->payloadConverter;
        $this->interfaceToCall->checkCompatibilityWithMethodParameters($methodArgumentConvertersPlusPayloadConverter);
    }
}