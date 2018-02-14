<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\Annotation;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\ModuleConfigurationAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\RouterAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilderWithParameterConverters;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Router\RouterBuilder;

/**
 * Class AnnotationRouterConfiguration
 * @package SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ModuleConfigurationAnnotation(moduleName="router-configuration")
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