<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Config\Annotation\ModuleConfiguration;

use SimplyCodedSoftware\Messaging\Annotation\Parameter\AllHeaders;
use SimplyCodedSoftware\Messaging\Annotation\Parameter\Expression;
use SimplyCodedSoftware\Messaging\Annotation\Parameter\Header;
use SimplyCodedSoftware\Messaging\Annotation\Parameter\MessageParameter;
use SimplyCodedSoftware\Messaging\Annotation\Parameter\Payload;
use SimplyCodedSoftware\Messaging\Annotation\Parameter\Reference;
use SimplyCodedSoftware\Messaging\Annotation\Parameter\Value;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilderWithParameterConverters;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\AllHeadersBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\ConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\ExpressionBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\HeaderBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MessageConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\PayloadBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\ReferenceBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\ValueBuilder;

/**
 * Class ParameterConverterAnnotationFactory
 * @package SimplyCodedSoftware\Messaging\Config\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
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
     * @param InterfaceToCall|null $relatedClassInterface
     * @param array $parameterConverterAnnotations
     *
     * @return array
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     * @throws \SimplyCodedSoftware\Messaging\Support\InvalidArgumentException
     */
    public function createParameterConverters(?InterfaceToCall $relatedClassInterface, array $parameterConverterAnnotations): array
    {
        $parameterConverters = [];

        foreach ($parameterConverterAnnotations as $parameterConverterAnnotation) {
            if ($parameterConverterAnnotation instanceof Header) {
                if ($parameterConverterAnnotation->isRequired) {
                    $parameterConverters[] = HeaderBuilder::create($parameterConverterAnnotation->parameterName, $parameterConverterAnnotation->headerName);
                } else {
                    $parameterConverters[] = HeaderBuilder::createOptional($parameterConverterAnnotation->parameterName, $parameterConverterAnnotation->headerName);
                }
            } else if ($parameterConverterAnnotation instanceof Payload) {
                $parameterConverters[] = PayloadBuilder::create($parameterConverterAnnotation->parameterName);
            } else if ($parameterConverterAnnotation instanceof MessageParameter) {
                $parameterConverters[] = MessageConverterBuilder::create($parameterConverterAnnotation->parameterName);
            } else if ($parameterConverterAnnotation instanceof Reference) {
                if ($parameterConverterAnnotation->referenceName) {
                    $parameterConverters[] = ReferenceBuilder::create($parameterConverterAnnotation->parameterName, $parameterConverterAnnotation->referenceName);
                }elseif ($relatedClassInterface) {
                    $parameterConverters[] = ReferenceBuilder::createFromParameterTypeHint($parameterConverterAnnotation->parameterName, $relatedClassInterface);
                }else {
                    $parameterConverters[] = ReferenceBuilder::createWithDynamicResolve($parameterConverterAnnotation->parameterName);
                }
            } else if ($parameterConverterAnnotation instanceof Expression) {
                $parameterConverters[] = ExpressionBuilder::create(
                    $parameterConverterAnnotation->parameterName, $parameterConverterAnnotation->expression
                );
            } else if ($parameterConverterAnnotation instanceof Value) {
                $parameterConverters[] = ValueBuilder::create($parameterConverterAnnotation->parameterName, $parameterConverterAnnotation->value);
            } else if ($parameterConverterAnnotation instanceof AllHeaders) {
                $parameterConverters[] = AllHeadersBuilder::createWith($parameterConverterAnnotation->parameterName);
            }
        }

        return $parameterConverters;
    }
}