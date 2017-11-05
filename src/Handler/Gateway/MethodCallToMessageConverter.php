<?php

namespace Messaging\Handler\Gateway;

use Messaging\Handler\Gateway\MethodParameterConverter\OnlyPayloadMessageParameterMethodArgumentConverter;
use Messaging\Message;
use Messaging\Support\Assert;
use Messaging\Support\InvalidArgumentException;

/**
 * Class MethodCallToMessageConverter
 * @package Messaging\Handler\Gateway
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
     * @param array|MethodArgument[] $methodArguments
     * @return Message
     */
    public function convertFor(array $methodArguments) : Message
    {
        Assert::allInstanceOfType($methodArguments, MethodArgument::class);

        $messageBuilder = null;
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
                }
            }
        }

        return $messageBuilder
                ->build();
    }

    private function initialize(string $interfaceToCall, string $methodName, ?PayloadMethodArgumentConverter $payloadMethodArgumentConverter, array $methodArgumentConverters) : void
    {
        Assert::allInstanceOfType($methodArgumentConverters, MethodArgumentConverter::class);

        $this->interfaceToCall = InterfaceToCall::create($interfaceToCall, $methodName);
        $this->methodArgumentConverters = $methodArgumentConverters;
        $this->payloadConverter = $payloadMethodArgumentConverter;

        if (is_null($payloadMethodArgumentConverter) && !$this->interfaceToCall->hasOneParameter()) {
            throw InvalidArgumentException::create("You need to pass method argument converts for {$this->interfaceToCall}");
        }
        if (is_null($payloadMethodArgumentConverter)) {
            $this->payloadConverter = new OnlyPayloadMessageParameterMethodArgumentConverter();
        }

        $methodArgumentConvertersPlusPayloadConverter = $methodArgumentConverters;
        $methodArgumentConvertersPlusPayloadConverter[] = $this->payloadConverter;
        $this->interfaceToCall->checkCompatibilityWithMethodParameters($methodArgumentConvertersPlusPayloadConverter);
    }
}