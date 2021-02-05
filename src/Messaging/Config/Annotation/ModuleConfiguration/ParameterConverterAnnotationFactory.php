<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration;

use Ecotone\Messaging\Attribute\Parameter\ConfigurationVariable;
use Ecotone\Messaging\Attribute\Parameter\Header;
use Ecotone\Messaging\Attribute\Parameter\Headers;
use Ecotone\Messaging\Attribute\Parameter\Payload;
use Ecotone\Messaging\Attribute\Parameter\Reference;
use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\AllHeadersBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\ConfigurationVariableBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\HeaderBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\HeaderExpressionBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\MessageConverterBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadExpressionBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\ReferenceBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvoker;
use Ecotone\Messaging\Support\Assert;

/**
 * Class ParameterConverterAnnotationFactory
 * @package Ecotone\Messaging\Config\Annotation
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
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

    public function createParameterWithDefaults(InterfaceToCall $relatedClassInterface, bool $ignorePayload): array
    {
        return
            MethodInvoker::createDefaultMethodParameters(
                $relatedClassInterface,
                $this->createParameterConverters($relatedClassInterface),
                [],
                null,
                $ignorePayload
            );
    }

    public function createParameterConverters(InterfaceToCall $relatedClassInterface): array
    {
        $parameterConverters = [];

        foreach ($relatedClassInterface->getInterfaceParameters() as $interfaceParameter) {
            $converter = $this->getConverterFor($interfaceParameter);

            if ($converter) {
                $parameterConverters[] = $converter;
            }
        }

        return $parameterConverters;
    }

    private function getConverterFor(InterfaceParameter $interfaceParameter): ParameterConverterBuilder|null
    {
        foreach ($interfaceParameter->getAnnotations() as $annotation) {
            if ($annotation instanceof Header) {
                if ($annotation->expression) {
                    return HeaderExpressionBuilder::create(
                        $interfaceParameter->getName(),
                        $annotation->getHeaderName(),
                        $annotation->getExpression(),
                        !$interfaceParameter->doesAllowNulls()
                    );
                } else if ($interfaceParameter->doesAllowNulls()) {
                    return HeaderBuilder::createOptional($interfaceParameter->getName(), $annotation->getHeaderName());
                } else {
                    return HeaderBuilder::create($interfaceParameter->getName(), $annotation->getHeaderName());
                }
            } else if ($annotation instanceof Payload) {
                if ($annotation->expression) {
                    return PayloadExpressionBuilder::create($interfaceParameter->getName(), $annotation->getExpression());
                } else {
                    return PayloadBuilder::create($interfaceParameter->getName());
                }
            } else if ($annotation instanceof Reference) {
                return ReferenceBuilder::create($interfaceParameter->getName(), $annotation->getReferenceName() ? $annotation->getReferenceName() : $interfaceParameter->getTypeHint());
            } else if ($annotation instanceof Headers) {
                return AllHeadersBuilder::createWith($interfaceParameter->getName());
            }else if ($annotation instanceof ConfigurationVariable) {
                return ConfigurationVariableBuilder::createFrom($annotation->getName(), $interfaceParameter);
            }
        }

        return $interfaceParameter->isMessage() ? MessageConverterBuilder::create($interfaceParameter->getName()) : null;
    }
}