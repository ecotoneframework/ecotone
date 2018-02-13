<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Config\ModuleConfiguration\AnnotationToBuilder;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\TransformerAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Config\Configuration;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilderWithParameterConverters;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Transformer\TransformerBuilder;

/**
 * Class AnnotationTransformerConfiguration
 * @package SimplyCodedSoftware\IntegrationMessaging\Config\ModuleConfiguration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AnnotationTransformerConfiguration extends MessageHandlerRegisterConfiguration
{
     /**
     * @inheritDoc
     */
    public function createMessageHandlerFrom(AnnotationRegistration $annotationRegistration): MessageHandlerBuilderWithParameterConverters
    {
        /** @var TransformerAnnotation $annotation */
        $annotation = $annotationRegistration->getAnnotation();

        return TransformerBuilder::create(
            $annotation->inputChannelName,
            $annotation->outputChannelName,
            $annotationRegistration->getReferenceName(),
            $annotationRegistration->getMethodName(),
            $annotationRegistration->getReferenceName()
        );
    }

    /**
     * @inheritDoc
     */
    public function getMessageHandlerAnnotation(): string
    {
        return TransformerAnnotation::class;
    }
}