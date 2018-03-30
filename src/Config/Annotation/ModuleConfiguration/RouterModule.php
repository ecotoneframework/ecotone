<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\ModuleAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\RouterAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationRegistration;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilderWithParameterConverters;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Router\RouterBuilder;

/**
 * Class AnnotationRouterConfiguration
 * @package SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\Annotation
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
    public function createMessageHandlerFrom(AnnotationRegistration $annotationRegistration): MessageHandlerBuilderWithParameterConverters
    {
        /** @var RouterAnnotation $annotation */
        $annotation = $annotationRegistration->getAnnotationForMethod();

        return RouterBuilder::create($annotation->inputChannel, $annotationRegistration->getReferenceName(), $annotationRegistration->getMethodName())
                ->setResolutionRequired($annotation->isResolutionRequired);
    }

    /**
     * @inheritDoc
     */
    public static function getMessageHandlerAnnotation(): string
    {
        return RouterAnnotation::class;
    }
}