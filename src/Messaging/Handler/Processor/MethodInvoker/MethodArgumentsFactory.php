<?php

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker;

use Ecotone\Messaging\Config\Container\AttributeDefinition;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\AllHeadersBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\AttributeBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\AttributeDefinitionBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\MessageConverterBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\PollingMetadataConverterBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\ReferenceBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\ValueBuilder;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\InvalidArgumentException;

/**
 * licence Apache-2.0
 */
class MethodArgumentsFactory
{
    /**
     * @param ParameterConverterBuilder[] $passedMethodParameterConverters
     * @param AttributeDefinition[] $endpointAnnotations
     * @return ParameterConverterBuilder[]
     */
    public static function createDefaultMethodParameters(InterfaceToCall $interfaceToCall, array $passedMethodParameterConverters, bool $ignorePayload = false): array
    {
        $passedArgumentsCount = count($passedMethodParameterConverters);
        $requiredArgumentsCount = count($interfaceToCall->getInterfaceParameters());
        $missingParametersAmount = $requiredArgumentsCount - $passedArgumentsCount;

        if ($missingParametersAmount > 0) {
            if ($missingParametersAmount >= 2 && $interfaceToCall->getSecondParameter()->getTypeDescriptor()->isArrayButNotClassBasedCollection()) {
                if (! $ignorePayload && ! self::hasPayloadConverter($passedMethodParameterConverters) && ! $interfaceToCall->getFirstParameter()->isAnnotation()) {
                    $passedMethodParameterConverters[] = self::createPayloadOrMessageParameter($interfaceToCall->getFirstParameter());
                }
                $passedMethodParameterConverters[] = AllHeadersBuilder::createWith($interfaceToCall->getSecondParameter()->getName());
            } elseif (! $ignorePayload && ! self::hasPayloadConverter($passedMethodParameterConverters) && ! $interfaceToCall->getFirstParameter()->isAnnotation()) {
                $passedMethodParameterConverters[] = self::createPayloadOrMessageParameter($interfaceToCall->getFirstParameter());
            }

            foreach ($interfaceToCall->getInterfaceParameters() as $interfaceParameter) {
                if ($interfaceParameter->isAnnotation() || self::hasParameterConverter($passedMethodParameterConverters, $interfaceParameter)) {
                    continue;
                }
                if ($interfaceParameter->isMessage()) {
                    $passedMethodParameterConverters[] = MessageConverterBuilder::create($interfaceParameter->getName());
                } elseif ($interfaceParameter->canBePassedIn(Type::object(PollingMetadata::class))) {
                    $passedMethodParameterConverters[] = new PollingMetadataConverterBuilder($interfaceParameter->getName());
                } elseif ($interfaceParameter->getTypeDescriptor()->isClassOrInterface()) {
                    $passedMethodParameterConverters[] = ReferenceBuilder::create($interfaceParameter->getName(), $interfaceParameter->getTypeHint());
                }
            }
        }

        $parametersForObjectToInvoke = $interfaceToCall->getInterfaceParameters();

        $orderedMethodArguments = [];
        foreach ($parametersForObjectToInvoke as $invokeParameter) {
            $orderedMethodArguments[] = self::getMethodArgumentFor($invokeParameter, $passedMethodParameterConverters, $interfaceToCall);
        }

        return $orderedMethodArguments;
    }

    /**
     * @param array<ParameterConverterBuilder> $passedMethodParameterConverters
     * @param array $endpointAnnotations
     * @return array<ParameterConverterBuilder>
     */
    public static function createInterceptedInterfaceAnnotationMethodParameters(
        InterfaceToCall $interfaceToCall,
        array $passedMethodParameterConverters,
        array $endpointAnnotations,
        InterfaceToCall $interceptedInterface
    ): array {
        foreach ($interfaceToCall->getInterfaceParameters() as $interfaceParameter) {
            if (! self::hasParameterConverter($passedMethodParameterConverters, $interfaceParameter) && $interfaceParameter->isAnnotation()) {
                $passedMethodParameterConverters[] = self::getAnnotationValueConverter($interfaceParameter, $interceptedInterface, $endpointAnnotations) ?? new ValueBuilder($interfaceParameter->getName(), null);
            }
        }

        return $passedMethodParameterConverters;
    }

    /**
     * @param AttributeDefinition[] $endpointAnnotations
     */
    public static function getAnnotationValueConverter(InterfaceParameter $interfaceParameter, InterfaceToCall $interceptedInterface, array $endpointAnnotations): AttributeBuilder|AttributeDefinitionBuilder|null
    {
        $interfaceParameterType = $interfaceParameter->getTypeDescriptor()->withoutNull();
        // Endpoint Annotations
        foreach ($endpointAnnotations as $endpointAnnotation) {
            if (Type::object($endpointAnnotation->getClassName())->equals($interfaceParameterType)) {
                return new AttributeDefinitionBuilder($interfaceParameter->getName(), $endpointAnnotation);
            }
        }
        foreach ($endpointAnnotations as $endpointAnnotation) {
            if ($interfaceParameterType->acceptType(Type::attribute($endpointAnnotation->getClassName()))) {
                return new AttributeDefinitionBuilder($interfaceParameter->getName(), $endpointAnnotation);
            }
        }

        // Method
        foreach ($interceptedInterface->getMethodAnnotations() as $endpointAnnotation) {
            if (Type::createFromVariable($endpointAnnotation)->equals($interfaceParameterType)) {
                return new AttributeBuilder($interfaceParameter->getName(), $endpointAnnotation, $interceptedInterface->getInterfaceName(), $interceptedInterface->getMethodName());
            }
        }
        foreach ($interceptedInterface->getMethodAnnotations() as $endpointAnnotation) {
            if ($interfaceParameterType->accepts($endpointAnnotation)) {
                return new AttributeBuilder($interfaceParameter->getName(), $endpointAnnotation, $interceptedInterface->getInterfaceName(), $interceptedInterface->getMethodName());
            }
        }

        // Class
        foreach ($interceptedInterface->getClassAnnotations() as $endpointAnnotation) {
            if (Type::createFromVariable($endpointAnnotation)->equals($interfaceParameterType)) {
                return new AttributeBuilder($interfaceParameter->getName(), $endpointAnnotation, $interceptedInterface->getInterfaceName());
            }
        }
        foreach ($interceptedInterface->getClassAnnotations() as $endpointAnnotation) {
            if ($interfaceParameterType->accepts($endpointAnnotation)) {
                return new AttributeBuilder($interfaceParameter->getName(), $endpointAnnotation, $interceptedInterface->getInterfaceName());
            }
        }

        return null;
    }

    /**
     * @param ParameterConverterBuilder[] $passedMethodParameterConverters
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

    private static function createPayloadOrMessageParameter(InterfaceParameter $parameter): ParameterConverterBuilder
    {
        return $parameter->isMessage() ? MessageConverterBuilder::create($parameter->getName()) : PayloadBuilder::create($parameter->getName());
    }

    /**
     * @param ParameterConverterBuilder[] $methodParameterConverters
     * @throws MessagingException
     */
    private static function getMethodArgumentFor(InterfaceParameter $invokeParameter, array $methodParameterConverters, InterfaceToCall $interfaceToCall): ParameterConverterBuilder
    {
        foreach ($methodParameterConverters as $methodParameterConverter) {
            if ($methodParameterConverter->isHandling($invokeParameter)) {
                return $methodParameterConverter;
            }
        }

        throw InvalidArgumentException::create("Invoked object {$interfaceToCall->getInterfaceName()}:{$interfaceToCall->getMethodName()} has no converter for parameter `{$invokeParameter->getName()}`");
    }

    private static function hasParameterConverter(array $passedMethodParameterConverters, InterfaceParameter $interfaceParameter): bool
    {
        foreach ($passedMethodParameterConverters as $passedMethodParameterConverter) {
            if ($passedMethodParameterConverter->isHandling($interfaceParameter)) {
                return true;
            }
        }

        return false;
    }
}
