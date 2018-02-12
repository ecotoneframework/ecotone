<?php

namespace SimplyCodedSoftware\Messaging\Config\ModuleConfiguration\AnnotationToBuilder;

use SimplyCodedSoftware\Messaging\Annotation\RouterAnnotation;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilderWithParameterConverters;
use SimplyCodedSoftware\Messaging\Handler\Router\RouterBuilder;

/**
 * Class AnnotationRouterConfiguration
 * @package SimplyCodedSoftware\Messaging\Config\ModuleConfiguration\AnnotationToBuilder
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AnnotationRouterConfiguration extends MessageHandlerRegisterConfiguration
{
    /**
     * @inheritDoc
     */
    public function createMessageHandlerFrom(AnnotationRegistration $annotationRegistration): MessageHandlerBuilderWithParameterConverters
    {
        /** @var RouterAnnotation $annotation */
        $annotation = $annotationRegistration->getAnnotation();

        return RouterBuilder::create($annotationRegistration->getReferenceName(), $annotation->inputChannel, $annotationRegistration->getReferenceName(), $annotationRegistration->getMethodName())
                ->setResolutionRequired($annotation->isResolutionRequired);
    }

    /**
     * @inheritDoc
     */
    public function getMessageHandlerAnnotation(): string
    {
        return RouterAnnotation::class;
    }
}