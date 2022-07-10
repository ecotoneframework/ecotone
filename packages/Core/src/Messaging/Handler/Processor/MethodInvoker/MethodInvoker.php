<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker;

use Doctrine\Common\Annotations\AnnotationException;
use Ecotone\Messaging\Conversion\ConversionException;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\ClassDefinition;
use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\MessageProcessor;
use Ecotone\Messaging\Handler\MethodArgument;
use Ecotone\Messaging\Handler\ParameterConverter;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\AllHeadersBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\AllHeadersConverter;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\InterceptorConverterBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\MessageConverter;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\MessageConverterBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadConverter;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\ReferenceBuilder;
use Ecotone\Messaging\Handler\ReferenceNotFoundException;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\InvalidArgumentException;
use ReflectionException;

/**
 * Class MethodInvocation
 * @package Messaging\Handler\ServiceActivator
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
final class MethodInvoker implements MessageProcessor
{
    /**
     * @var object|string
     */
    private $objectToInvokeOn;
    private string $objectMethodName;
    /**
     * @var ParameterConverter[]
     */
    private array $orderedMethodArguments;
    private bool $isCalledStatically;
    private ConversionService $conversionService;
    private InterfaceToCall $interfaceToCall;
    private array $aroundMethodInterceptors = [];
    /**
     * @var object[]
     */
    private array $endpointAnnotations;
    private bool $canInterceptorReplaceArguments;

    /**
     * MethodInvocation constructor.
     * @param $objectToInvokeOn
     * @param string $objectMethodName
     * @param array|ParameterConverter[] $methodParameterConverters
     * @param InterfaceToCall $interfaceToCall
     * @param ConversionService $conversionService
     * @param AroundMethodInterceptor[] $aroundMethodInterceptors
     * @param object[] $endpointAnnotations
     * @param bool $canInterceptorReplaceArguments
     * @throws InvalidArgumentException
     * @throws MessagingException
     */
    private function __construct($objectToInvokeOn, string $objectMethodName, array $methodParameterConverters, InterfaceToCall $interfaceToCall, ConversionService $conversionService, array $aroundMethodInterceptors, array $endpointAnnotations, bool $canInterceptorReplaceArguments)
    {
        Assert::allInstanceOfType($methodParameterConverters, ParameterConverter::class);

        $this->init($objectToInvokeOn, $objectMethodName, $methodParameterConverters, $interfaceToCall);
        $this->objectToInvokeOn = $objectToInvokeOn;
        $this->conversionService = $conversionService;
        $this->objectMethodName = $objectMethodName;
        $this->interfaceToCall = $interfaceToCall;
        $this->aroundMethodInterceptors = $aroundMethodInterceptors;
        $this->endpointAnnotations = $endpointAnnotations;
        $this->canInterceptorReplaceArguments = $canInterceptorReplaceArguments;
    }

    /**
     * @param ParameterConverterBuilder[] $methodParametersConverterBuilders
     */
    public static function createWith(InterfaceToCall $interfaceToCall, $objectToInvokeOn, array $methodParametersConverterBuilders, ReferenceSearchService $referenceSearchService, ?ChannelResolver $channelResolver = null, array $aroundInterceptors = [], array $endpointAnnotations = []): self
    {
        $methodParametersConverterBuilders = self::createDefaultMethodParameters($interfaceToCall, $methodParametersConverterBuilders, $endpointAnnotations, null, false);
        $methodParameterConverters         = [];
        foreach ($methodParametersConverterBuilders as $methodParameter) {
            $methodParameterConverters[] = $methodParameter->build($referenceSearchService);
        }
        if ($aroundInterceptors) {
            $aroundInterceptors = AroundInterceptorReference::createAroundInterceptorsWithChannel($channelResolver, $referenceSearchService, $aroundInterceptors);
        }

        /** @var ConversionService $conversionService */
        $conversionService = $referenceSearchService->get(ConversionService::REFERENCE_NAME);

        return new self($objectToInvokeOn, $interfaceToCall->getMethodName(), $methodParameterConverters, $interfaceToCall, $conversionService, $aroundInterceptors, $endpointAnnotations, true);
    }

    /**
     * @param ParameterConverterBuilder[] $passedMethodParameterConverters
     * @return ParameterConverterBuilder[]|ParameterConverter[]
     * @throws InvalidArgumentException
     * @throws MessagingException
     */
    public static function createDefaultMethodParameters(InterfaceToCall $interfaceToCall, array $passedMethodParameterConverters, array $endpointAnnotations, ?InterfaceToCall $interceptedInterface, bool $ignorePayload): array
    {
        $passedArgumentsCount = count($passedMethodParameterConverters);
        $requiredArgumentsCount = count($interfaceToCall->getInterfaceParameters());
        $missingParametersAmount = $requiredArgumentsCount - $passedArgumentsCount;

        if ($missingParametersAmount > 0) {
            foreach ($interfaceToCall->getInterfaceParameters() as $interfaceParameter) {
                if (!self::hasParameterConverter($passedMethodParameterConverters, $interfaceParameter) && $interfaceParameter->getTypeDescriptor()->isClassOrInterface()) {
                    if ($interceptedInterface && ($interfaceParameter->isAnnotation() || $interceptedInterface->getInterfaceType()->equals($interfaceParameter->getTypeDescriptor()))) {
                        $passedMethodParameterConverters[] = InterceptorConverterBuilder::create($interfaceParameter->getName(), $interceptedInterface, $endpointAnnotations);
                        $missingParametersAmount--;
                    }
                }
            }

            if ($missingParametersAmount >= 2 && $interfaceToCall->getSecondParameter()->getTypeDescriptor()->isNonCollectionArray()) {
                if (!$ignorePayload && !self::hasPayloadConverter($passedMethodParameterConverters) && !$interfaceToCall->getFirstParameter()->isAnnotation()) {
                    $passedMethodParameterConverters[] = self::createPayloadOrMessageParameter($interfaceToCall->getFirstParameter());
                }
                $passedMethodParameterConverters[] = AllHeadersBuilder::createWith($interfaceToCall->getSecondParameter()->getName());
            } elseif (!$ignorePayload && !self::hasPayloadConverter($passedMethodParameterConverters) && !$interfaceToCall->getFirstParameter()->isAnnotation()) {
                $passedMethodParameterConverters[] = self::createPayloadOrMessageParameter($interfaceToCall->getFirstParameter());
            }

            foreach ($interfaceToCall->getInterfaceParameters() as $interfaceParameter) {
                if ($interfaceParameter->isAnnotation()) {
                    continue;
                }
                if (!self::hasParameterConverter($passedMethodParameterConverters, $interfaceParameter) && $interfaceParameter->isMessage()) {
                    $passedMethodParameterConverters[] = MessageConverterBuilder::create($interfaceParameter->getName());
                }elseif (!self::hasParameterConverter($passedMethodParameterConverters, $interfaceParameter) && $interfaceParameter->getTypeDescriptor()->isClassOrInterface()) {
                    $passedMethodParameterConverters[] = ReferenceBuilder::create($interfaceParameter->getName(), $interfaceParameter->getTypeHint());
                }
            }
        }

        return $passedMethodParameterConverters;
    }

    /**
     * @param array $passedMethodParameterConverters
     * @return bool
     */
    private static function hasPayloadConverter(array $passedMethodParameterConverters): bool
    {
        foreach ($passedMethodParameterConverters as $parameterConverter) {
            if ($parameterConverter instanceof PayloadBuilder) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param InterfaceParameter $parameter
     * @return ParameterConverter|ParameterConverterBuilder
     */
    private static function createPayloadOrMessageParameter(InterfaceParameter $parameter)
    {
        return $parameter->isMessage() ? MessageConverterBuilder::create($parameter->getName()) : PayloadBuilder::create($parameter->getName());
    }

    /**
     * @param string $invokedClass
     * @param string $methodToInvoke
     * @param InterfaceParameter $invokeParameter
     * @param array|ParameterConverter[] $methodParameterConverters
     * @return ParameterConverter
     * @throws MessagingException
     */
    private function getMethodArgumentFor(string $invokedClass, string $methodToInvoke, InterfaceParameter $invokeParameter, array $methodParameterConverters): ParameterConverter
    {
        foreach ($methodParameterConverters as $methodParameterConverter) {
            if ($methodParameterConverter->isHandling($invokeParameter)) {
                return $methodParameterConverter;
            }
        }

        throw InvalidArgumentException::create("Invoked object {$invokedClass}:{$methodToInvoke} has no converter for parameter `{$invokeParameter->getName()}`");
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
     * @param ParameterConverterBuilder[] $methodParameterConverterBuilders
     * @param InterfaceParameter $interfaceParameter
     * @return bool
     */
    public static function hasParameterConverterFor(array $methodParameterConverterBuilders, InterfaceParameter $interfaceParameter): bool
    {
        foreach ($methodParameterConverterBuilders as $methodParameterConverterBuilder) {
            if ($methodParameterConverterBuilder->isHandling($interfaceParameter)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function processMessage(Message $message)
    {
        $methodCall = $this->getMethodCall($message);

        if (!$this->aroundMethodInterceptors) {
            return call_user_func_array([$this->objectToInvokeOn, $this->objectMethodName], $methodCall->getMethodArgumentValues());
        }

        $methodInvokerProcessor = new MethodInvokerChainProcessor(
            $methodCall,
            $this,
            $this->aroundMethodInterceptors,
            $this->objectToInvokeOn,
            $this->interfaceToCall,
            $message,
            $this->endpointAnnotations
        );

        return $methodInvokerProcessor->proceed();
    }

    /**
     * @param Message $message
     * @return MethodCall
     * @throws InvalidArgumentException
     * @throws MessagingException
     */
    private function getMethodCall(Message $message): MethodCall
    {
        $sourceMediaType = $message->getHeaders()->containsKey(MessageHeaders::CONTENT_TYPE)
            ? MediaType::parseMediaType($message->getHeaders()->get(MessageHeaders::CONTENT_TYPE))
            : MediaType::createApplicationXPHP();
        $parameterMediaType = MediaType::createApplicationXPHP();

        $methodArguments = [];
        $count = count($this->orderedMethodArguments);

        for ($index = 0; $index < $count; $index++) {
            $interfaceParameter = $this->interfaceToCall->getParameterAtIndex($index);
            $data = $this->orderedMethodArguments[$index]->getArgumentFrom(
                $this->interfaceToCall,
                $interfaceParameter,
                $message,
                $this->endpointAnnotations
            );
            $isPayloadConverter = $this->orderedMethodArguments[$index] instanceof PayloadConverter;
            $sourceTypeDescriptor = $isPayloadConverter && $sourceMediaType->hasTypeParameter()
                ? TypeDescriptor::create($sourceMediaType->getParameter("type"))
                : TypeDescriptor::createFromVariable($data);

            $currentParameterMediaType = $isPayloadConverter ? $sourceMediaType : MediaType::createApplicationXPHP();
            $parameterType = $this->interfaceToCall->getParameterAtIndex($index)->getTypeDescriptor();

            if (!($sourceTypeDescriptor->isCompatibleWith($parameterType))) {
                $convertedData = null;
                if (!$parameterType->isCompoundObjectType() && !$parameterType->isAbstractClass() && !$parameterType->isInterface() && !$parameterType->isAnything() && !$parameterType->isUnionType() && $this->canConvertParameter(
                    $sourceTypeDescriptor,
                    $currentParameterMediaType,
                    $parameterType,
                    $parameterMediaType
                )) {
                    $convertedData = $this->doConversion($this->interfaceToCall, $interfaceParameter, $data, $sourceTypeDescriptor, $currentParameterMediaType, $parameterType, $parameterMediaType);
                } else if ($message->getHeaders()->containsKey(MessageHeaders::TYPE_ID)) {
                    $resolvedTargetParameterType = $message->getHeaders()->containsKey(MessageHeaders::TYPE_ID) ? TypeDescriptor::create($message->getHeaders()->get(MessageHeaders::TYPE_ID)) : $parameterType;
                    if ($this->canConvertParameter(
                            $sourceTypeDescriptor,
                            $currentParameterMediaType,
                            $resolvedTargetParameterType,
                            $parameterMediaType
                        )
                    ) {
                        $convertedData = $this->doConversion($this->interfaceToCall, $interfaceParameter, $data, $sourceTypeDescriptor, $currentParameterMediaType, $resolvedTargetParameterType, $parameterMediaType);
                    }
                }

                if (!is_null($convertedData)) {
                    $data = $convertedData;
                }else {
                    if ($parameterType->isUnionType()) {
                        throw InvalidArgumentException::create("Can not call {$this->interfaceToCall} lack of information which type should be used to deserialization. Consider adding __TYPE__ header to indicate which union type it should be resolved to.");
                    }elseif (!$currentParameterMediaType->isCompatibleWith($parameterMediaType) && !$sourceTypeDescriptor->isCompatibleWith($parameterType)) {
                        throw InvalidArgumentException::create("Can not call {$this->interfaceToCall}. Lack of Media Type Converter for {$currentParameterMediaType}:{$sourceTypeDescriptor} to {$parameterMediaType}:{$parameterType}");
                    }
                }
            }

            $methodArguments[] = MethodArgument::createWith($interfaceParameter, $data);
        }

        return MethodCall::createWith($methodArguments, $this->canInterceptorReplaceArguments);
    }

    /**
     * @param Type $requestType
     * @param MediaType $requestMediaType
     * @param Type $parameterType
     * @param MediaType $parameterMediaType
     * @return bool
     */
    private function canConvertParameter(Type $requestType, MediaType $requestMediaType, Type $parameterType, MediaType $parameterMediaType): bool
    {
        return $this->conversionService->canConvert(
            $requestType,
            $requestMediaType,
            $parameterType,
            $parameterMediaType
        );
    }

    private function doConversion(InterfaceToCall $interfaceToCall, InterfaceParameter $interfaceParameterToConvert, $data, Type $requestType, MediaType $requestMediaType, Type $parameterType, MediaType $parameterMediaType): mixed
    {
        try {
            return $this->conversionService->convert(
                $data,
                $requestType,
                $requestMediaType,
                $parameterType,
                $parameterMediaType
            );
        }catch (ConversionException $exception) {
            throw ConversionException::createFromPreviousException("There is a problem with conversion for {$interfaceToCall} on parameter {$interfaceParameterToConvert->getName()}: " . $exception->getMessage(), $exception);
        }
    }

    /**
     * @return InterfaceToCall
     */
    public function getInterfaceToCall(): InterfaceToCall
    {
        return $this->interfaceToCall;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->interfaceToCall;
    }

    /**
     * @param array $passedMethodParameterConverters
     * @param InterfaceParameter $interfaceParameter
     * @return bool
     */
    private static function hasParameterConverter(array $passedMethodParameterConverters, InterfaceParameter $interfaceParameter): bool
    {
        foreach ($passedMethodParameterConverters as $passedMethodParameterConverter) {
            if ($passedMethodParameterConverter->isHandling($interfaceParameter)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $objectToInvokeOn
     * @param string $objectMethodName
     * @param array|ParameterConverter[] $methodParameterConverters
     * @param InterfaceToCall $interfaceToCall
     * @throws InvalidArgumentException
     * @throws MessagingException
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

        $parametersForObjectToInvoke = $interfaceToCall->getInterfaceParameters();

        $orderedMethodArguments = [];
        foreach ($parametersForObjectToInvoke as $invokeParameter) {
            $orderedMethodArguments[] = $this->getMethodArgumentFor($this->objectToClassName($objectToInvokeOn), $objectMethodName, $invokeParameter, $methodParameterConverters);
        }

        $this->objectToInvokeOn = $objectToInvokeOn;
        $this->objectMethodName = $objectMethodName;
        $this->orderedMethodArguments = $orderedMethodArguments;
    }
}