<?php

namespace SimplyCodedSoftware\Messaging\Config\ModuleConfiguration\AnnotationToBuilder;

use SimplyCodedSoftware\Messaging\Annotation\TransformerAnnotation;
use SimplyCodedSoftware\Messaging\Config\Configuration;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilderWithParameterConverters;
use SimplyCodedSoftware\Messaging\Handler\Transformer\TransformerBuilder;

/**
 * Class AnnotationTransformerConfiguration
 * @package SimplyCodedSoftware\Messaging\Config\ModuleConfiguration
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