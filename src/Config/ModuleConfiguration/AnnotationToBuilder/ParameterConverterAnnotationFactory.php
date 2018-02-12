<?php

namespace SimplyCodedSoftware\Messaging\Config\ModuleConfiguration\AnnotationToBuilder;
use SimplyCodedSoftware\Messaging\Annotation\ParameterConverter\HeaderParameterConverterAnnotation;
use SimplyCodedSoftware\Messaging\Annotation\ParameterConverter\MessageParameterConverterAnnotation;
use SimplyCodedSoftware\Messaging\Annotation\ParameterConverter\PayloadParameterConverterAnnotation;
use SimplyCodedSoftware\Messaging\Annotation\ParameterConverter\ReferenceServiceConverterAnnotation;
use SimplyCodedSoftware\Messaging\Annotation\ParameterConverterAnnotation;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilderWithParameterConverters;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\Builder\HeaderParameterConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\Builder\MessageParameterConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\Builder\PayloadParameterConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\Builder\ReferenceServiceParameterConverterBuilder;

/**
 * Class ParameterConverterAnnotationFactory
 * @package SimplyCodedSoftware\Messaging\Config\ModuleConfiguration
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
    public static function create() : self
    {
        return new self();
    }

    /**
     * @param MessageHandlerBuilderWithParameterConverters $messageHandlerBuilder
     * @param string $relatedClassName
     * @param string $methodName
     * @param array|ParameterConverterAnnotation[] $parameterConverterAnnotations
     * @return void
     */
    public function configureParameterConverters(MessageHandlerBuilderWithParameterConverters $messageHandlerBuilder, string $relatedClassName, string $methodName, array $parameterConverterAnnotations) : void
    {
        $interfaceToCall = InterfaceToCall::create($relatedClassName, $methodName);
        $parameterConverters = [];

        foreach ($parameterConverterAnnotations as $parameterConverterAnnotation) {
            if ($parameterConverterAnnotation instanceof HeaderParameterConverterAnnotation) {
                $parameterConverters[] = HeaderParameterConverterBuilder::create($parameterConverterAnnotation->parameterName, $parameterConverterAnnotation->headerName);
            }else if ($parameterConverterAnnotation instanceof PayloadParameterConverterAnnotation) {
                $parameterConverters[] = PayloadParameterConverterBuilder::create($parameterConverterAnnotation->parameterName);
            }else if ($parameterConverterAnnotation instanceof MessageParameterConverterAnnotation) {
                $parameterConverters[] = MessageParameterConverterBuilder::create($parameterConverterAnnotation->parameterName);
            }else if ($parameterConverterAnnotation instanceof ReferenceServiceConverterAnnotation) {
                $parameter = $interfaceToCall->getParameterWithName($parameterConverterAnnotation->parameterName);
                $referenceName = $parameterConverterAnnotation->referenceName ? $parameterConverterAnnotation->referenceName : $parameter->getTypeHint();
                $messageHandlerBuilder->registerRequiredReference($referenceName);

                $parameterConverters[] = ReferenceServiceParameterConverterBuilder::create(
                    $parameterConverterAnnotation->parameterName, $referenceName, $messageHandlerBuilder
                );
            }
        }

        $messageHandlerBuilder->withMethodParameterConverters($parameterConverters);
    }
}