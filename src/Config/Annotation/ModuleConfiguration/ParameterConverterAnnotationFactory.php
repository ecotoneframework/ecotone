<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageToParameter\MessageParameterAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageToParameter\MessageToExpressionParameterAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageToParameter\MessageToHeaderParameterAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageToParameter\MessageToPayloadParameterAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageToParameter\MessageToReferenceServiceAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilderWithParameterConverters;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\MessageParameterConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\MessageToExpressionEvaluationParameterConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\MessageToHeaderParameterConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\MessageToPayloadParameterConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\MessageToReferenceServiceParameterConverterBuilder;

/**
 * Class ParameterConverterAnnotationFactory
 * @package SimplyCodedSoftware\IntegrationMessaging\Config\Annotation
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
     * @param MessageHandlerBuilderWithParameterConverters $messageHandlerBuilder
     * @param string $relatedClassName
     * @param string $methodName
     * @param array $parameterConverterAnnotations
     * @return void
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException
     */
    public function configureParameterConverters(MessageHandlerBuilderWithParameterConverters $messageHandlerBuilder, string $relatedClassName, string $methodName, array $parameterConverterAnnotations): void
    {
        $messageHandlerBuilder->withMethodParameterConverters($this->createParameterConverters($messageHandlerBuilder, $relatedClassName, $methodName, $parameterConverterAnnotations));
    }

    /**
     * @param MessageHandlerBuilderWithParameterConverters $messageHandlerBuilder
     * @param string                                       $relatedClassName
     * @param string                                       $methodName
     * @param array                                        $parameterConverterAnnotations
     *
     * @return array
     */
    public function createParameterConverters(MessageHandlerBuilderWithParameterConverters $messageHandlerBuilder, string $relatedClassName, string $methodName, array $parameterConverterAnnotations): array
    {
        $interfaceToCall     = InterfaceToCall::create($relatedClassName, $methodName);
        $parameterConverters = [];

        foreach ($parameterConverterAnnotations as $parameterConverterAnnotation) {
            if ($parameterConverterAnnotation instanceof MessageToHeaderParameterAnnotation) {
                $parameterConverters[] = MessageToHeaderParameterConverterBuilder::create($parameterConverterAnnotation->parameterName, $parameterConverterAnnotation->headerName)
                    ->setRequired($parameterConverterAnnotation->isRequired);
            } else if ($parameterConverterAnnotation instanceof MessageToPayloadParameterAnnotation) {
                $parameterConverters[] = MessageToPayloadParameterConverterBuilder::create($parameterConverterAnnotation->parameterName);
            } else if ($parameterConverterAnnotation instanceof MessageParameterAnnotation) {
                $parameterConverters[] = MessageParameterConverterBuilder::create($parameterConverterAnnotation->parameterName);
            } else if ($parameterConverterAnnotation instanceof MessageToReferenceServiceAnnotation) {
                $parameter     = $interfaceToCall->getParameterWithName($parameterConverterAnnotation->parameterName);
                $referenceName = $parameterConverterAnnotation->referenceName ? $parameterConverterAnnotation->referenceName : $parameter->getTypeHint();
                $messageHandlerBuilder->registerRequiredReference($referenceName);

                $parameterConverters[] = MessageToReferenceServiceParameterConverterBuilder::create(
                    $parameterConverterAnnotation->parameterName, $referenceName, $messageHandlerBuilder
                );
            } else if ($parameterConverterAnnotation instanceof MessageToExpressionParameterAnnotation) {
                $parameterConverters[] = MessageToExpressionEvaluationParameterConverterBuilder::createWith(
                    $parameterConverterAnnotation->parameterName, $parameterConverterAnnotation->expression, $messageHandlerBuilder
                );
            }
        }

        return $parameterConverters;
    }
}