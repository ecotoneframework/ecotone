<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker;

use SimplyCodedSoftware\IntegrationMessaging\Conversion\AutoCollectionConversionService;
use SimplyCodedSoftware\IntegrationMessaging\Conversion\ConversionService;
use SimplyCodedSoftware\IntegrationMessaging\Conversion\MediaType;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InterfaceParameter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InterfaceToCallRegistry;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageProcessor;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ParameterConverter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ParameterConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\TypeDescriptor;
use SimplyCodedSoftware\IntegrationMessaging\Message;
use SimplyCodedSoftware\IntegrationMessaging\MessageHeaders;
use SimplyCodedSoftware\IntegrationMessaging\Support\Assert;
use SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;

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
     * @var ConversionService
     */
    private $conversionService;
    /**
     * @var bool
     */
    private $wrapWithMessage;
    /**
     * @var InterfaceToCall
     */
    private $interfaceToCall;

    /**
     * MethodInvocation constructor.
     * @param $objectToInvokeOn
     * @param string $objectMethodName
     * @param array|ParameterConverter[] $methodParameterConverters
     * @param bool $wrapWithMessage
     * @param InterfaceToCall $interfaceToCall
     * @param ConversionService $conversionService
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    private function __construct($objectToInvokeOn, string $objectMethodName, array $methodParameterConverters, bool $wrapWithMessage, InterfaceToCall $interfaceToCall, ConversionService $conversionService)
    {
        Assert::allInstanceOfType($methodParameterConverters, ParameterConverter::class);

        $this->init($objectToInvokeOn, $objectMethodName, $methodParameterConverters, $interfaceToCall);
        $this->objectToInvokeOn = $objectToInvokeOn;
        $this->conversionService = $conversionService;
        $this->objectMethodName = $objectMethodName;
        $this->wrapWithMessage = $wrapWithMessage;
        $this->interfaceToCall = $interfaceToCall;
    }

    /**
     * @param $objectToInvokeOn
     * @param string $objectMethodName
     * @param array|ParameterConverter[] $methodParameterConverters
     * @param InterfaceToCall $interfaceToCall
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    private function init($objectToInvokeOn, string $objectMethodName, array $methodParameterConverters, InterfaceToCall $interfaceToCall): void
    {
        $this->isCalledStatically = false;
        if (!is_object($objectToInvokeOn)) {
            if (!$interfaceToCall->isStaticallyCalled()) {
                throw InvalidArgumentException::create("Reference to invoke must be object given {$objectToInvokeOn}");
            }
            $this->isCalledStatically = true;
        }

        $parametersForObjectToInvoke = $interfaceToCall->getParameters();
        $passedArgumentsCount = count($methodParameterConverters);
        $requiredArgumentsCount = count($interfaceToCall->getParameters());

        if ($this->canBeInvokedWithDefaultArgument($passedArgumentsCount, $requiredArgumentsCount)) {
            $firstParameter = $interfaceToCall->getFirstParameter();
            if ($interfaceToCall->hasFirstParameterMessageTypeHint()) {
                $methodParameterConverters = [MessageConverter::create($firstParameter->getName())];
            } else {
                $methodParameterConverters = [PayloadConverter::create($firstParameter->getName())];
            }

            $passedArgumentsCount = 1;
        }

        if (!$this->hasEnoughArguments($passedArgumentsCount, $requiredArgumentsCount)) {
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
     * @param $requiredArgumentsCount
     * @param $passedArgumentsCount
     * @return bool
     */
    private function canBeInvokedWithDefaultArgument(int $passedArgumentsCount, int $requiredArgumentsCount): bool
    {
        return $requiredArgumentsCount === 1 && $passedArgumentsCount === 0;
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
     * @param string $invokedClass
     * @param string $methodToInvoke
     * @param InterfaceParameter $invokeParameter
     * @param array|ParameterConverter[] $methodParameterConverters
     * @return ParameterConverter
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    private function getMethodArgumentFor(string $invokedClass, string $methodToInvoke, InterfaceParameter $invokeParameter, array $methodParameterConverters): ParameterConverter
    {
        foreach ($methodParameterConverters as $methodParameterConverter) {
            if ($methodParameterConverter->isHandling($invokeParameter)) {
                return $methodParameterConverter;
            }
        }

        throw InvalidArgumentException::create("Invoked object {$invokedClass} with method {$methodToInvoke} has no converter for {$invokeParameter->getName()}");
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
     * @param $objectToInvokeOn
     * @param string $objectMethodName
     * @param array|ParameterConverterBuilder[] $methodParameters
     * @param ReferenceSearchService $referenceSearchService
     * @return self
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceNotFoundException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public static function createWithMessageWrapper($objectToInvokeOn, string $objectMethodName, array $methodParameters, ReferenceSearchService $referenceSearchService): self
    {
        /** @var InterfaceToCallRegistry $interfaceToCallRegistry */
        $interfaceToCallRegistry = $referenceSearchService->get(InterfaceToCallRegistry::REFERENCE_NAME);
        /** @var ConversionService $conversionService */
        $conversionService = $referenceSearchService->get(ConversionService::REFERENCE_NAME);
        $messageConverters = [];
        foreach ($methodParameters as $methodParameter) {
            $messageConverters[] = $methodParameter->build($referenceSearchService);
        }

        return new self($objectToInvokeOn, $objectMethodName, $messageConverters, true, $interfaceToCallRegistry->getFor($objectToInvokeOn, $objectMethodName), $conversionService);
    }

    /**
     * @param $objectToInvokeOn
     * @param string $objectMethodName
     * @param array $methodParameters
     * @param ReferenceSearchService $referenceSearchService
     * @return MethodInvoker
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceNotFoundException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public static function createWith($objectToInvokeOn, string $objectMethodName, array $methodParameters, ReferenceSearchService $referenceSearchService): self
    {
        /** @var InterfaceToCallRegistry $interfaceToCallRegistry */
        $interfaceToCallRegistry = $referenceSearchService->get(InterfaceToCallRegistry::REFERENCE_NAME);
        /** @var ConversionService $conversionService */
        $conversionService = $referenceSearchService->get(ConversionService::REFERENCE_NAME);
        $messageConverters = [];
        foreach ($methodParameters as $methodParameter) {
            $messageConverters[] = $methodParameter->build($referenceSearchService);
        }

        return new self($objectToInvokeOn, $objectMethodName, $messageConverters, false, $interfaceToCallRegistry->getFor($objectToInvokeOn, $objectMethodName), $conversionService);
    }

    /**
     * @inheritDoc
     */
    public function processMessage(Message $message)
    {
        $payload = call_user_func_array([$this->objectToInvokeOn, $this->objectMethodName], $this->getMethodArguments($message));

        if (is_null($payload)) {
            return null;
        }

        if ($payload instanceof Message || is_subclass_of($payload, Message::class)) {
            return $payload;
        }

        return $payload;
    }

    /**
     * @return InterfaceToCall
     */
    public function getInterfaceToCall() : InterfaceToCall
    {
        return $this->interfaceToCall;
    }

    /**
     * @param Message $message
     * @return array
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    private function getMethodArguments(Message $message): array
    {
        $sourceMediaType = $message->getHeaders()->containsKey(MessageHeaders::CONTENT_TYPE)
            ? MediaType::parseMediaType($message->getHeaders()->get(MessageHeaders::CONTENT_TYPE))
            : MediaType::createApplicationXPHPObject();
        $replyMediaType = MediaType::createApplicationXPHPObject();

        $methodArguments = [];
        $count = count($this->orderedMethodArguments);

        for ($index = 0; $index < $count; $index++) {
            $data = $this->orderedMethodArguments[$index]->getArgumentFrom($this->interfaceToCall->getParameterAtIndex($index), $message);
            $isPayloadConverter = $this->orderedMethodArguments[$index] instanceof PayloadConverter;
            $sourceTypeDescriptor = $isPayloadConverter && $sourceMediaType->hasTypeParameter()
                ? TypeDescriptor::create($sourceMediaType->getParameter("type"))
                : TypeDescriptor::createFromVariable($data);

            if ($this->canConvertParameter(
                $index,
                $sourceTypeDescriptor,
                $isPayloadConverter ? $sourceMediaType : MediaType::createApplicationXPHPObject(),
                $replyMediaType
            )) {
                $data = $this->doConversion($data, $index, $sourceTypeDescriptor, $sourceMediaType, $replyMediaType);
            }

            $methodArguments[] = $data;
        }

        return $methodArguments;
    }

    /**
     * @param int $index
     * @param MediaType $requestMediaType
     * @param MediaType $replyMediaType
     * @param TypeDescriptor $requestType
     * @return bool
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    private function canConvertParameter(int $index, TypeDescriptor $requestType, MediaType $requestMediaType, MediaType $replyMediaType): bool
    {
        return $this->conversionService->canConvert(
            $requestType,
            $requestMediaType,
            $this->interfaceToCall->getParameterAtIndex($index)->getTypeDescriptor(),
            $replyMediaType
        );
    }

    /**
     * @param $data
     * @param int $index
     * @param MediaType $requestMediaType
     * @param MediaType $replyMediaType
     * @param TypeDescriptor $requestType
     * @return mixed
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    private function doConversion($data, int $index, TypeDescriptor $requestType, MediaType $requestMediaType, MediaType $replyMediaType)
    {
        $data = $this->conversionService->convert(
            $data,
            $requestType,
            $requestMediaType,
            $this->interfaceToCall->getParameterAtIndex($index)->getTypeDescriptor(),
            $replyMediaType
        );

        return $data;
    }

    public function __toString()
    {
        $objectToInvokeOn = get_class($this->objectToInvokeOn);

        return "Object {$objectToInvokeOn}, method {$this->objectMethodName}";
    }
}