<?php

namespace SimplyCodedSoftware\Messaging\Config\Annotation\ModuleConfiguration;

use SimplyCodedSoftware\Messaging\Annotation\ModuleAnnotation;
use SimplyCodedSoftware\Messaging\Annotation\Router;
use SimplyCodedSoftware\Messaging\Config\Annotation\AnnotationRegistration;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilderWithParameterConverters;
use SimplyCodedSoftware\Messaging\Handler\Router\RouterBuilder;

/**
 * Class AnnotationRouterConfiguration
 * @package SimplyCodedSoftware\Messaging\Config\Annotation\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ModuleAnnotation()
 */
class RouterModule extends MessageHandlerRegisterConfiguration
{
    public const MODULE_NAME = "routerModule";

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return self::MODULE_NAME;
    }

    /**
     * @inheritDoc
     */
    public static function createMessageHandlerFrom(AnnotationRegistration $annotationRegistration): MessageHandlerBuilderWithParameterConverters
    {
        /** @var Router $annotation */
        $annotation = $annotationRegistration->getAnnotationForMethod();

        return RouterBuilder::create($annotationRegistration->getReferenceName(), $annotationRegistration->getMethodName())
                ->withEndpointId($annotation->endpointId)
                ->withInputChannelName($annotation->inputChannelName)
                ->setResolutionRequired($annotation->isResolutionRequired);
    }

    /**
     * @inheritDoc
     */
    public static function getMessageHandlerAnnotation(): string
    {
        return Router::class;
    }
}