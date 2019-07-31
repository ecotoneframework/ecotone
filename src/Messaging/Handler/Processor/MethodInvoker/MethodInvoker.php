<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker;

use Doctrine\Common\Annotations\AnnotationException;
use ReflectionException;
use SimplyCodedSoftware\Messaging\Conversion\ConversionService;
use SimplyCodedSoftware\Messaging\Conversion\MediaType;
use SimplyCodedSoftware\Messaging\Handler\InterfaceParameter;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCallRegistry;
use SimplyCodedSoftware\Messaging\Handler\MessageProcessor;
use SimplyCodedSoftware\Messaging\Handler\MethodArgument;
use SimplyCodedSoftware\Messaging\Handler\ParameterConverter;
use SimplyCodedSoftware\Messaging\Handler\ParameterConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\Converter\AllHeadersBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\Converter\AllHeadersConverter;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\Converter\MessageConverter;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\Converter\MessageConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadConverter;
use SimplyCodedSoftware\Messaging\Handler\ReferenceNotFoundException;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\TypeDescriptor;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessageHeaders;
use SimplyCodedSoftware\Messaging\MessagingException;
use SimplyCodedSoftware\Messaging\Support\Assert;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;

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
     * @return array
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
                $passedMethodParameterConverters[] = self::createPayloadOrMessageParameter($interfaceToCall->getFirstParameter(), $shouldBeBuild);
                $passedMethodParameterConverters[] = $shouldBeBuild ? new AllHeadersConverter($interfaceToCall->getSecondParameter()->getName()) : AllHeadersBuilder::createWith($interfaceToCall->getSecondParameter()->getName());
            } else {
                $passedMethodParameterConverters[] = self::createPayloadOrMessageParameter($interfaceToCall->getFirstParameter(), $shouldBeBuild);
            }
        }

        return $passedMethodParameterConverters;
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

    /**
     * @param $objectToInvokeOn
     * @param string $objectMethodName
     * @param ParameterConverterBuilder[] $methodParameters
     * @param ReferenceSearchService $referenceSearchService
     * @param AroundInterceptorReference[] $orderedAroundMethodInterceptorReferences
     * @param object[] $endpointAnnotations
     * @return MethodInvoker
     * @throws InvalidArgumentException
     * @throws AnnotationException
     * @throws ReflectionException
     * @throws ReferenceNotFoundException
     * @throws MessagingException
     */
    public static function createWithInterceptors($objectToInvokeOn, string $objectMethodName, array $methodParameters, ReferenceSearchService $referenceSearchService, array $orderedAroundMethodInterceptorReferences, array $endpointAnnotations = []): self
    {
        $messageConverters = [];
        foreach ($methodParameters as $methodParameter) {
            $messageConverters[] = $methodParameter->build($referenceSearchService);
        }

        return self::createWithBuiltParameterConverters($objectToInvokeOn, $objectMethodName, $messageConverters, $referenceSearchService, AroundInterceptorReference::createAroundInterceptors($referenceSearchService, $orderedAroundMethodInterceptorReferences), $endpointAnnotations, true);
    }

    /**
     * @param $objectToInvokeOn
     * @param string $objectMethodName
     * @param ParameterConverterBuilder[] $methodParameters
     * @param ReferenceSearchService $referenceSearchService
     * @param AroundInterceptorReference[] $orderedAroundMethodInterceptorReferences
     * @param object[] $endpointAnnotations
     * @return MethodInvoker
     * @throws InvalidArgumentException
     * @throws AnnotationException
     * @throws ReflectionException
     * @throws ReferenceNotFoundException
     * @throws MessagingException
     */
    public static function createWithInterceptorsNotChangingCallArguments($objectToInvokeOn, string $objectMethodName, array $methodParameters, ReferenceSearchService $referenceSearchService, array $orderedAroundMethodInterceptorReferences, array $endpointAnnotations): self
    {
        $messageConverters = [];
        foreach ($methodParameters as $methodParameter) {
            $messageConverters[] = $methodParameter->build($referenceSearchService);
        }

        return self::createWithBuiltParameterConverters($objectToInvokeOn, $objectMethodName, $messageConverters, $referenceSearchService, AroundInterceptorReference::createAroundInterceptors($referenceSearchService, $orderedAroundMethodInterceptorReferences), $endpointAnnotations, false);
    }

    /**
     * @inheritDoc
     */
    public function processMessage(Message $message)
    {
        $methodCall = $this->getMethodCall($message);

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
            : MediaType::createApplicationXPHPObject();
        $replyMediaType = MediaType::createApplicationXPHPObject();

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

            $currentParameterMediaType = $isPayloadConverter ? $sourceMediaType : MediaType::createApplicationXPHPObject();
            if ($this->canConvertParameter(
                $index,
                $sourceTypeDescriptor,
                $currentParameterMediaType,
                $replyMediaType
            )) {
                $data = $this->doConversion($data, $index, $sourceTypeDescriptor, $currentParameterMediaType, $replyMediaType);
            }

            $methodArguments[] = MethodArgument::createWith($interfaceParameter, $data);
        }

        return MethodCall::createWith($methodArguments, $this->canInterceptorReplaceArguments);
    }

    /**
     * @param int $index
     * @param MediaType $requestMediaType
     * @param MediaType $replyMediaType
     * @param TypeDescriptor $requestType
     * @return bool
     * @throws InvalidArgumentException
     * @throws MessagingException
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
     * @throws MessagingException
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
}