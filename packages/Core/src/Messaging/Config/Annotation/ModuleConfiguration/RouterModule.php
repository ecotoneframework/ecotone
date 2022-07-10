<?php

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration;

use Ecotone\AnnotationFinder\AnnotatedFinding;
use Ecotone\Messaging\Attribute\ModuleAnnotation;
use Ecotone\Messaging\Attribute\Router;
use Ecotone\Messaging\Config\Annotation\AnnotatedDefinitionReference;
use Ecotone\Messaging\Handler\MessageHandlerBuilderWithParameterConverters;
use Ecotone\Messaging\Handler\Router\RouterBuilder;

#[ModuleAnnotation]
class RouterModule extends MessageHandlerRegisterConfiguration
{
    public const MODULE_NAME = "routerModule";

    /**
     * @inheritDoc
     */
    public static function createMessageHandlerFrom(AnnotatedFinding $annotationRegistration): MessageHandlerBuilderWithParameterConverters
    {
        /** @var Router $annotation */
        $annotation = $annotationRegistration->getAnnotationForMethod();

        return RouterBuilder::create(AnnotatedDefinitionReference::getReferenceFor($annotationRegistration), $annotationRegistration->getMethodName())
            ->withEndpointId($annotation->getEndpointId())
            ->withInputChannelName($annotation->getInputChannelName())
            ->setResolutionRequired($annotation->isResolutionRequired());
    }

    /**
     * @inheritDoc
     */
    public static function getMessageHandlerAnnotation(): string
    {
        return Router::class;
    }
}