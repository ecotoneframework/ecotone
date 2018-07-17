<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker;

use SimplyCodedSoftware\IntegrationMessaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilderWithParameterConverters;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageProcessor;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ParameterConverter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ParameterConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Message;
use SimplyCodedSoftware\IntegrationMessaging\Support\Assert;
use SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException;

/**
 * Class MethodInvocation
 * @package Messaging\Handler\ServiceActivator
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
final class MethodInvoker implements MessageProcessor
{
    /**
     * @var object
     */
    private $objectToInvokeOn;
    /**
     * @var string
     */
    private $objectMethodName;
    /**
     * @var ParameterConverter[]
     */
    private $orderedMethodArguments;
    /**
     * @var bool
     */
    private $isCalledStatically;

    /**
     * MethodInvocation constructor.
     * @param $objectToInvokeOn
     * @param string $objectMethodName
     * @param array|ParameterConverter[] $methodParameterConverters
     * @throws InvalidArgumentException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    private function __construct($objectToInvokeOn, string $objectMethodName, array $methodParameterConverters)
    {
        Assert::allInstanceOfType($methodParameterConverters, ParameterConverter::class);

        $this->init($objectToInvokeOn, $objectMethodName, $methodParameterConverters);
    }

    /**
     * @param $objectToInvokeOn
     * @param string $objectMethodName
     * @param array|ParameterConverterBuilder[] $methodParameters
     * @param ReferenceSearchService $referenceSearchService
     * @return self
     * @throws InvalidArgumentException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public static function createWith($objectToInvokeOn, string $objectMethodName, array $methodParameters, ReferenceSearchService $referenceSearchService) : self
    {
        $messageConverters = [];
        foreach ($methodParameters as $methodParameter) {
            $messageConverters[] = $methodParameter->build($referenceSearchService);
        }

        return new self($objectToInvokeOn, $objectMethodName, $messageConverters);
    }

    /**
     * @inheritDoc
     */
    public function processMessage(Message $message)
    {
        return call_user_func_array([$this->objectToInvokeOn, $this->objectMethodName], $this->getMethodArguments($message));
    }

    /**
     * @param $objectToInvokeOn
     * @param string $objectMethodName
     * @param array|ParameterConverter[] $methodParameterConverters
     * @throws InvalidArgumentException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    private function init($objectToInvokeOn, string $objectMethodName, array $methodParameterConverters) : void
    {
        $this->isCalledStatically = false;
        if (!is_object($objectToInvokeOn)) {
            $interfaceToCall = InterfaceToCall::create($objectToInvokeOn, $objectMethodName);

            if (!$interfaceToCall->isStaticallyCalled()) {
                throw InvalidArgumentException::create("Reference to invoke must be object given {$objectToInvokeOn}");
            }
            $this->isCalledStatically = true;
        }

        if (!$this->hasObjectMethod($objectToInvokeOn, $objectMethodName)) {
            throw InvalidArgumentException::create("Object {$this->objectToClassName($objectToInvokeOn)} does not contain method {$objectMethodName}");
        }

        $objectToInvokeReflection = new \ReflectionMethod($objectToInvokeOn, $objectMethodName);
        $parametersForObjectToInvoke = $objectToInvokeReflection->getParameters();
        $passedArgumentsCount = count($methodParameterConverters);
        $requiredArgumentsCount = count($objectToInvokeReflection->getParameters());

        if ($this->canBeInvokedWithDefaultArgument($passedArgumentsCount, $requiredArgumentsCount)) {
            $firstArgument = $parametersForObjectToInvoke[0];

            if ((string)$firstArgument->getType() === Message::class) {
                $methodParameterConverters = [MessageConverter::create($firstArgument->getName())];
            }else {
                $methodParameterConverters = [PayloadConverter::create($firstArgument->getName())];
            }

            $passedArgumentsCount = 1;
        }

        if (!$this->hasEnoughArguments($passedArgumentsCount, $requiredArgumentsCount)) {
            $interfaceToCall = InterfaceToCall::createFromUnknownType($objectToInvokeOn, $objectMethodName);
            throw InvalidArgumentException::create("Object {$interfaceToCall} requires {$requiredArgumentsCount} parameters converters, but passed {$passedArgumentsCount}");
        }

        $orderedMethodArguments = [];
        foreach ($parametersForObjectToInvoke as $invokeParameter) {
            $orderedMethodArguments[] = $this->getMethodArgumentFor($this->objectToClassName($objectToInvokeOn), $objectMethodName, $invokeParameter, $methodParameterConverters);
        }

        $this->objectToInvokeOn = $objectToInvokeOn;
        $this->objectMethodName = $objectMethodName;
        $this->orderedMethodArguments = $orderedMethodArguments;
    }

    /**
     * @param Message $message
     * @return array
     */
    private function getMethodArguments(Message $message) : array
    {
        $methodArguments = [];

        foreach ($this->orderedMethodArguments as $methodArgument) {
            $methodArguments[] = $methodArgument->getArgumentFrom($message);
        }

        return $methodArguments;
    }

    /**
     * @param $objectToInvokeOn
     * @param string $objectMethodName
     * @return bool
     */
    private function hasObjectMethod($objectToInvokeOn, string $objectMethodName): bool
    {
        return method_exists($objectToInvokeOn, $objectMethodName);
    }

    /**
     * @param string|object $objectToInvokeOn
     * @return string
     */
    private function objectToClassName($objectToInvokeOn): string
    {
        return $this->isCalledStatically ? $objectToInvokeOn : get_class($objectToInvokeOn);
    }

    /**
     * @param int $passedArgumentsCount
     * @param int $requiredArgumentsCount
     * @return bool
     */
    private function hasEnoughArguments(int $passedArgumentsCount, int $requiredArgumentsCount): bool
    {
        return $passedArgumentsCount === $requiredArgumentsCount;
    }

    /**
     * @param $requiredArgumentsCount
     * @param $passedArgumentsCount
     * @return bool
     */
    private function canBeInvokedWithDefaultArgument(int $passedArgumentsCount, int $requiredArgumentsCount): bool
    {
        return $requiredArgumentsCount === 1 && $passedArgumentsCount === 0;
    }

    /**
     * @param string $invokedClass
     * @param string $methodToInvoke
     * @param \ReflectionParameter $invokeParameter
     * @param array|ParameterConverter[] $methodParameterConverters
     * @return ParameterConverter
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    private function getMethodArgumentFor(string $invokedClass, string $methodToInvoke, \ReflectionParameter $invokeParameter, array $methodParameterConverters): ParameterConverter
    {
        foreach ($methodParameterConverters as $methodParameterConverter) {
            if ($methodParameterConverter->isHandling($invokeParameter)) {
                return $methodParameterConverter;
            }
        }

        throw InvalidArgumentException::create("Invoked object {$invokedClass} with method {$methodToInvoke} has no converter for {$invokeParameter->getName()}");
    }

    public function __toString()
    {
        $objectToInvokeOn = get_class($this->objectToInvokeOn);

        return "Object {$objectToInvokeOn}, method {$this->objectMethodName}";
    }
}