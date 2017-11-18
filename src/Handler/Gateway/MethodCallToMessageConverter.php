<?php

namespace Messaging\Handler\Gateway;

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
     * @var array|MethodArgumentConverter[]
     */
    private $methodArgumentConverters;

    /**
     * MethodCallToMessageConverter constructor.
     * @param string $interfaceToCall
     * @param string $methodName
     * @param array|MethodArgumentConverter[] $methodArgumentConverters
     */
    public function __construct(string $interfaceToCall, string $methodName, array $methodArgumentConverters)
    {
        $this->initialize($interfaceToCall, $methodName, $methodArgumentConverters);
    }

    /**
     * @param array|MethodArgument[] $methodArguments
     * @return Message
     */
    public function convertFor(array $methodArguments) : Message
    {
        Assert::allInstanceOfType($methodArguments, MethodArgument::class);
        $messageBuilder = MessageBuilder::withPayload("empty");

        foreach ($methodArguments as $methodArgument) {
            foreach ($this->methodArgumentConverters as $methodParameterConverter) {
                if ($methodParameterConverter->hasParameterNameAs($methodArgument)) {
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
     * @param array|MethodArgumentConverter[] $methodArgumentConverters
     * @throws \Messaging\MessagingException
     */
    private function initialize(string $interfaceToCall, string $methodName, array $methodArgumentConverters) : void
    {
        Assert::allInstanceOfType($methodArgumentConverters, MethodArgumentConverter::class);

        $this->interfaceToCall = InterfaceToCall::create($interfaceToCall, $methodName);

        if (empty($methodArgumentConverters) && $this->interfaceToCall->hasMoreThanOneParameter()) {
            throw InvalidArgumentException::create("You need to pass method argument converts for {$this->interfaceToCall}");
        }

        if (empty($methodArgumentConverters) && $this->interfaceToCall->hasSingleArguments()) {
            $methodArgumentConverters = [new OnlyPayloadMessageParameterMethodArgumentConverter()];
        }

        $this->interfaceToCall->checkCompatibilityWithMethodParameters($methodArgumentConverters);
        $this->methodArgumentConverters = $methodArgumentConverters;
    }
}