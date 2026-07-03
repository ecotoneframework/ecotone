<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration;

use Closure;
use Ecotone\Messaging\Attribute\Parameter\ConfigurationVariable;
use Ecotone\Messaging\Attribute\Parameter\Fetch;
use Ecotone\Messaging\Attribute\Parameter\Header;
use Ecotone\Messaging\Attribute\Parameter\Headers;
use Ecotone\Messaging\Attribute\Parameter\Payload;
use Ecotone\Messaging\Attribute\Parameter\Reference;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\Container\AttributeDeclaration;
use Ecotone\Messaging\Handler\ClosureExpression\ClosureExpressionParameterConverterBuilder;
use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\AllHeadersBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\ConfigurationVariableBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\FetchAggregateConverterBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\HeaderBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\HeaderExpressionBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\MessageConverterBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadExpressionBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\ReferenceBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodArgumentsFactory;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Modelling\Attribute\IgnorePayload;

/**
 * Class ParameterConverterAnnotationFactory
 * @package Ecotone\Messaging\Config\Annotation
 * @author  Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class ParameterConverterAnnotationFactory
{
    private function __construct()
    {
    }

    /**
     * @return ParameterConverterAnnotationFactory
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * @return ParameterConverterBuilder[]
     */
    public static function createParameterWithDefaults(InterfaceToCall $relatedClassInterface): array
    {
        return
            MethodArgumentsFactory::createDefaultMethodParameters(
                $relatedClassInterface,
                self::createParameterConverters($relatedClassInterface),
                $relatedClassInterface->hasMethodAnnotation(Type::object(IgnorePayload::class))
            );
    }

    /**
     * @return ParameterConverterBuilder[]
     */
    public static function createParameterConverters(InterfaceToCall $relatedClassInterface): array
    {
        $parameterConverters = [];

        foreach ($relatedClassInterface->getInterfaceParameters() as $interfaceParameter) {
            $converter = self::getConverterFor($interfaceParameter, $relatedClassInterface);

            if ($converter) {
                $parameterConverters[] = $converter;
            }
        }

        return $parameterConverters;
    }

    public static function getConverterFor(InterfaceParameter $interfaceParameter, ?InterfaceToCall $relatedClassInterface = null): ParameterConverterBuilder|null
    {
        foreach ($interfaceParameter->getAnnotations() as $annotation) {
            if ($annotation instanceof Header) {
                if ($annotation->expression instanceof Closure) {
                    return self::closureExpressionConverterFor($interfaceParameter, $annotation, $relatedClassInterface);
                }
                if ($annotation->expression) {
                    return HeaderExpressionBuilder::create(
                        $interfaceParameter->getName(),
                        $annotation->getHeaderName(),
                        $annotation->getExpression(),
                        ! $interfaceParameter->doesAllowNulls()
                    );
                } elseif ($interfaceParameter->doesAllowNulls()) {
                    return HeaderBuilder::createOptional($interfaceParameter->getName(), $annotation->getHeaderName());
                } else {
                    return HeaderBuilder::create($interfaceParameter->getName(), $annotation->getHeaderName());
                }
            } elseif ($annotation instanceof Payload) {
                if ($annotation->expression instanceof Closure) {
                    return self::closureExpressionConverterFor($interfaceParameter, $annotation, $relatedClassInterface);
                }
                if ($annotation->expression) {
                    return PayloadExpressionBuilder::create($interfaceParameter->getName(), $annotation->getExpression());
                } else {
                    return PayloadBuilder::create($interfaceParameter->getName());
                }
            } elseif ($annotation instanceof Reference) {
                if ($annotation->getExpression() instanceof Closure) {
                    return self::closureExpressionConverterFor($interfaceParameter, $annotation, $relatedClassInterface);
                }

                return ReferenceBuilder::create(
                    $interfaceParameter->getName(),
                    $annotation->getReferenceName() ?: $interfaceParameter->getTypeHint(),
                    $annotation->getExpression(),
                );
            } elseif ($annotation instanceof Headers) {
                return AllHeadersBuilder::createWith($interfaceParameter->getName());
            } elseif ($annotation instanceof ConfigurationVariable) {
                return ConfigurationVariableBuilder::createFrom($annotation->getName(), $interfaceParameter);
            } elseif ($annotation instanceof Fetch) {
                $expression = $annotation->getExpression();
                if ($expression instanceof Closure && $relatedClassInterface === null) {
                    throw ConfigurationException::create(sprintf('Closure expression inside %s attribute is not supported for parameter `%s` in this context.', get_class($annotation), $interfaceParameter->getName()));
                }

                return FetchAggregateConverterBuilder::create(
                    $interfaceParameter,
                    $expression,
                    $expression instanceof Closure
                        ? new AttributeDeclaration(get_class($annotation), $relatedClassInterface->getInterfaceName(), $relatedClassInterface->getMethodName(), $interfaceParameter->getName())
                        : null,
                );
            }
        }

        return $interfaceParameter->isMessage() ? MessageConverterBuilder::create($interfaceParameter->getName()) : null;
    }

    private static function closureExpressionConverterFor(InterfaceParameter $interfaceParameter, object $annotation, ?InterfaceToCall $relatedClassInterface): ClosureExpressionParameterConverterBuilder
    {
        if ($relatedClassInterface === null) {
            throw ConfigurationException::create(sprintf('Closure expression inside %s attribute is not supported for parameter `%s` in this context.', get_class($annotation), $interfaceParameter->getName()));
        }

        return ClosureExpressionParameterConverterBuilder::create($interfaceParameter->getName(), $annotation, $relatedClassInterface);
    }
}
