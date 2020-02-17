<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker;

use Doctrine\Common\Annotations\AnnotationException;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\MessageProcessor;
use Ecotone\Messaging\Handler\MethodArgument;
use Ecotone\Messaging\Handler\ParameterConverter;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\AllHeadersBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\AllHeadersConverter;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\MessageConverter;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\MessageConverterBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadConverter;
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
     * @var InterfaceToCall
     */
    private $interfaceToCall;
    /**
     * @var AroundMethodInterceptor[]
     */
    private $aroundMethodInterceptors = [];
    /**
     * @var object[]
     */
    private $endpointAnnotations;
    /**
     * @var bool
     */
    private $canInterceptorReplaceArguments;

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

        $methodParameterConverters = self::createDefaultMethodParameters($interfaceToCall, $methodParameterConverters, true);

        $orderedMethodArguments = [];
        foreach ($parametersForObjectToInvoke as $invokeParameter) {
            $orderedMethodArguments[] = $this->getMethodArgumentFor($this->objectToClassName($objectToInvokeOn), $objectMethodName, $invokeParameter, $methodParameterConverters);
        }

        $this->objectToInvokeOn = $objectToInvokeOn;
        $this->objectMethodName = $objectMethodName;
        $this->orderedMethodArguments = $orderedMethodArguments;
    }

    /**
     * @param InterfaceToCall $interfaceToCall
     * @param ParameterConverterBuilder[] $passedMethodParameterConverters
     * @param bool $shouldBeBuild
     * @return ParameterConverterBuilder[]|ParameterConverter[]
     * @throws InvalidArgumentException
     * @throws MessagingException
     */
    public static function createDefaultMethodParameters(InterfaceToCall $interfaceToCall, array $passedMethodParameterConverters, bool $shouldBeBuild): array
    {
        $passedArgumentsCount = count($passedMethodParameterConverters);
        $requiredArgumentsCount = count($interfaceToCall->getInterfaceParameters());
        $missingParametersAmount = $requiredArgumentsCount - $passedArgumentsCount;

        if ($missingParametersAmount > 0) {
            if ($missingParametersAmount >= 2 && $interfaceToCall->getSecondParameter()->getTypeDescriptor()->isNonCollectionArray()) {
                if (!self::hasPayloadConverter($passedMethodParameterConverters)) {
                    $passedMethodParameterConverters[] = self::createPayloadOrMessageParameter($interfaceToCall->getFirstParameter(), $shouldBeBuild);
                }
                $passedMethodParameterConverters[] = $shouldBeBuild ? new AllHeadersConverter($interfaceToCall->getSecondParameter()->getName()) : AllHeadersBuilder::createWith($interfaceToCall->getSecondParameter()->getName());
            } else {
                if (!self::hasPayloadConverter($passedMethodParameterConverters)) {
                    $passedMethodParameterConverters[] = self::createPayloadOrMessageParameter($interfaceToCall->getFirstParameter(), $shouldBeBuild);
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
     *
     * @param bool $shouldBeBuild
     * @return ParameterConverter|ParameterConverterBuilder
     */
    private static function createPayloadOrMessageParameter(InterfaceParameter $parameter, bool $shouldBeBuild)
    {
        if ($parameter->isMessage()) {
            return $shouldBeBuild ? MessageConverter::create($parameter->getName()) : MessageConverterBuilder::create($parameter->getName());
        } else {
            return $shouldBeBuild ? PayloadConverter::create($parameter->getName()) : PayloadBuilder::create($parameter->getName());
        }
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
     * @param ParameterConverterBuilder[] $methodParameters
     * @param ReferenceSearchService $referenceSearchService
     * @return MethodInvoker
     * @throws InvalidArgumentException
     * @throws AnnotationException
     * @throws ReflectionException
     * @throws ReferenceNotFoundException
     * @throws MessagingException
     */
    public static function createWith($objectToInvokeOn, string $objectMethodName, array $methodParameters, ReferenceSearchService $referenceSearchService): self
    {
        $messageConverters = [];
        foreach ($methodParameters as $methodParameter) {
            $messageConverters[] = $methodParameter->build($referenceSearchService);
        }

        return self::createWithBuiltParameterConverters($objectToInvokeOn, $objectMethodName, $messageConverters, $referenceSearchService, [], [], true);
    }

    /**
     * @param $objectToInvokeOn
     * @param string $objectMethodName
     * @param ParameterConverter[] $methodParameters
     * @param ReferenceSearchService $referenceSearchService
     * @param AroundMethodInterceptor[] $interceptorsReferences
     * @param object[] $endpointAnnotations
     * @param bool $canInterceptorsReplaceMethodArguments
     * @return MethodInvoker
     * @throws AnnotationException
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws ReferenceNotFoundException
     * @throws ReflectionException
     */
    public static function createWithBuiltParameterConverters($objectToInvokeOn, string $objectMethodName, array $methodParameters, ReferenceSearchService $referenceSearchService, array $interceptorsReferences, array $endpointAnnotations, bool $canInterceptorsReplaceMethodArguments): self
    {
        /** @var InterfaceToCallRegistry $interfaceToCallRegistry */
        $interfaceToCallRegistry = $referenceSearchService->get(InterfaceToCallRegistry::REFERENCE_NAME);
        /** @var ConversionService $conversionService */
        $conversionService = $referenceSearchService->get(ConversionService::REFERENCE_NAME);

        return new self($objectToInvokeOn, $objectMethodName, $methodParameters, $interfaceToCallRegistry->getFor($objectToInvokeOn, $objectMethodName), $conversionService, $interceptorsReferences, $endpointAnnotations, $canInterceptorsReplaceMethodArguments);
    }

    public static function createWithInterceptorsWithChannel($objectToInvokeOn, string $objectMethodName, array $methodParameters, ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService, array $orderedAroundMethodInterceptorReferences, array $endpointAnnotations = []): self
    {
        $messageConverters = [];
        foreach ($methodParameters as $methodParameter) {
            $messageConverters[] = $methodParameter->build($referenceSearchService);
        }

        return self::createWithBuiltParameterConverters($objectToInvokeOn, $objectMethodName, $messageConverters, $referenceSearchService, AroundInterceptorReference::createAroundInterceptorsWithChannel($channelResolver, $referenceSearchService, $orderedAroundMethodInterceptorReferences), $endpointAnnotations, true);
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

    public static function createWithInterceptorsNotChangingCallArgumentsWithChannel($objectToInvokeOn, string $objectMethodName, array $methodParameters, ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService, array $orderedAroundMethodInterceptorReferences, array $endpointAnnotations): self
    {
        $messageConverters = [];
        foreach ($methodParameters as $methodParameter) {
            $messageConverters[] = $methodParameter->build($referenceSearchService);
        }

        return self::createWithBuiltParameterConverters($objectToInvokeOn, $objectMethodName, $messageConverters, $referenceSearchService, AroundInterceptorReference::createAroundInterceptorsWithChannel($channelResolver, $referenceSearchService, $orderedAroundMethodInterceptorReferences), $endpointAnnotations, false);
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
                if ($this->canConvertParameter(
                    $sourceTypeDescriptor,
                    $currentParameterMediaType,
                    $parameterType,
                    $parameterMediaType
                )) {
                    $convertedData = $this->doConversion($data, $sourceTypeDescriptor, $currentParameterMediaType, $parameterType, $parameterMediaType);
                } else if ($message->getHeaders()->containsKey(MessageHeaders::TYPE_ID)) {
                    $typeDescriptor = TypeDescriptor::create($message->getHeaders()->get(MessageHeaders::TYPE_ID));
                    if ($parameterType->isCompatibleWith($typeDescriptor)
                        &&
                        $this->canConvertParameter(
                            $sourceTypeDescriptor,
                            $currentParameterMediaType,
                            $typeDescriptor,
                            $parameterMediaType
                        )
                    ) {
                        $convertedData = $this->doConversion($data, $sourceTypeDescriptor, $currentParameterMediaType, $typeDescriptor, $parameterMediaType);
                    }
                }

                if (!is_null($convertedData)) {
                    $data = $convertedData;
                }else {
                    if (!$currentParameterMediaType->isCompatibleWith($parameterMediaType) && !$sourceTypeDescriptor->isCompatibleWith($parameterType)) {
//                    if (!$currentParameterMediaType->isCompatibleWith($parameterMediaType)) {
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

    /**
     * @param $data
     * @param Type $requestType
     * @param MediaType $requestMediaType
     * @param Type $parameterType
     * @param MediaType $parameterMediaType
     * @return mixed
     */
    private function doConversion($data, Type $requestType, MediaType $requestMediaType, Type $parameterType, MediaType $parameterMediaType)
    {
        $data = $this->conversionService->convert(
            $data,
            $requestType,
            $requestMediaType,
            $parameterType,
            $parameterMediaType
        );

        return $data;
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
}