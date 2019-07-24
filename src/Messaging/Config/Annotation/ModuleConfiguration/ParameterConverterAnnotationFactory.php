<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Config\Annotation\ModuleConfiguration;

use SimplyCodedSoftware\Messaging\Annotation\Parameter\Expression;
use SimplyCodedSoftware\Messaging\Annotation\Parameter\Header;
use SimplyCodedSoftware\Messaging\Annotation\Parameter\Headers;
use SimplyCodedSoftware\Messaging\Annotation\Parameter\MessageParameter;
use SimplyCodedSoftware\Messaging\Annotation\Parameter\Payload;
use SimplyCodedSoftware\Messaging\Annotation\Parameter\Reference;
use SimplyCodedSoftware\Messaging\Annotation\Parameter\Value;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\Converter\AllHeadersBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\Converter\HeaderExpressionBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\Converter\HeaderBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\Converter\MessageConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadExpressionBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\Converter\ReferenceBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\Converter\ValueBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\ConverterBuilder;

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
                if ($parameterConverterAnnotation->expression) {
                    $parameterConverters[] = HeaderExpressionBuilder::create(
                        $parameterConverterAnnotation->parameterName,
                        $parameterConverterAnnotation->headerName,
                        $parameterConverterAnnotation->expression,
                        $parameterConverterAnnotation->isRequired
                    );
                }else if ($parameterConverterAnnotation->isRequired) {
                    $parameterConverters[] = HeaderBuilder::create($parameterConverterAnnotation->parameterName, $parameterConverterAnnotation->headerName);
                } else {
                    $parameterConverters[] = HeaderBuilder::createOptional($parameterConverterAnnotation->parameterName, $parameterConverterAnnotation->headerName);
                }
            } else if ($parameterConverterAnnotation instanceof Payload) {
                if ($parameterConverterAnnotation->expression) {
                    $parameterConverters[] = PayloadExpressionBuilder::create($parameterConverterAnnotation->parameterName, $parameterConverterAnnotation->expression);
                }else {
                    $parameterConverters[] = PayloadBuilder::create($parameterConverterAnnotation->parameterName);
                }
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
            } else if ($parameterConverterAnnotation instanceof Headers) {
                $parameterConverters[] = AllHeadersBuilder::createWith($parameterConverterAnnotation->parameterName);
            }
        }

        return $parameterConverters;
    }
}