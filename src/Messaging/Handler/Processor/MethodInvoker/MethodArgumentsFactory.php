<?php

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker;

use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\ParameterConverter;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\AllHeadersBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\InterceptorConverterBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\MessageConverterBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\ReferenceBuilder;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\InvalidArgumentException;

class MethodArgumentsFactory
{
    public static function createDefaultMethodParameters(InterfaceToCall $interfaceToCall, array $passedMethodParameterConverters, array $endpointAnnotations, ?InterfaceToCall $interceptedInterface, bool $ignorePayload): array
    {
        $passedArgumentsCount = count($passedMethodParameterConverters);
        $requiredArgumentsCount = count($interfaceToCall->getInterfaceParameters());
        $missingParametersAmount = $requiredArgumentsCount - $passedArgumentsCount;

        if ($missingParametersAmount > 0) {
            foreach ($interfaceToCall->getInterfaceParameters() as $interfaceParameter) {
                if (! self::hasParameterConverter($passedMethodParameterConverters, $interfaceParameter) && $interfaceParameter->getTypeDescriptor()->isClassOrInterface()) {
                    if ($interceptedInterface && ($interfaceParameter->isAnnotation() || $interceptedInterface->getInterfaceType()->equals($interfaceParameter->getTypeDescriptor()))) {
                        $passedMethodParameterConverters[] = InterceptorConverterBuilder::create($interfaceParameter->getName(), $interceptedInterface, $endpointAnnotations);
                        $missingParametersAmount--;
                    }
                }
            }

            if ($missingParametersAmount >= 2 && $interfaceToCall->getSecondParameter()->getTypeDescriptor()->isNonCollectionArray()) {
                if (! $ignorePayload && ! self::hasPayloadConverter($passedMethodParameterConverters) && ! $interfaceToCall->getFirstParameter()->isAnnotation()) {
                    $passedMethodParameterConverters[] = self::createPayloadOrMessageParameter($interfaceToCall->getFirstParameter());
                }
                $passedMethodParameterConverters[] = AllHeadersBuilder::createWith($interfaceToCall->getSecondParameter()->getName());
            } elseif (! $ignorePayload && ! self::hasPayloadConverter($passedMethodParameterConverters) && ! $interfaceToCall->getFirstParameter()->isAnnotation()) {
                $passedMethodParameterConverters[] = self::createPayloadOrMessageParameter($interfaceToCall->getFirstParameter());
            }

            foreach ($interfaceToCall->getInterfaceParameters() as $interfaceParameter) {
                if ($interfaceParameter->isAnnotation()) {
                    continue;
                }
                if (! self::hasParameterConverter($passedMethodParameterConverters, $interfaceParameter) && $interfaceParameter->isMessage()) {
                    $passedMethodParameterConverters[] = MessageConverterBuilder::create($interfaceParameter->getName());
                } elseif (! self::hasParameterConverter($passedMethodParameterConverters, $interfaceParameter) && $interfaceParameter->getTypeDescriptor()->isClassOrInterface()) {
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

    private static function createPayloadOrMessageParameter(InterfaceParameter $parameter): ParameterConverterBuilder
    {
        return $parameter->isMessage() ? MessageConverterBuilder::create($parameter->getName()) : PayloadBuilder::create($parameter->getName());
    }

    /**
     * @param array|ParameterConverter[] $methodParameterConverters
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
