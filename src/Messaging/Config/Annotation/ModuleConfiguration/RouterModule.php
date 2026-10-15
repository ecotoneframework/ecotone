<?php

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration;

use Ecotone\AnnotationFinder\AnnotatedFinding;
use Ecotone\Api\Attribute\ModuleAnnotation;
use Ecotone\Api\Attribute\Router;
use Ecotone\Api\ExtensionObject\ModulePackageList;
use Ecotone\Messaging\Config\Annotation\AnnotatedDefinitionReference;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\MessageHandlerBuilderWithParameterConverters;
use Ecotone\Messaging\Handler\Router\RouterBuilder;

#[ModuleAnnotation]
/**
 * licence Apache-2.0
 */
class RouterModule extends MessageHandlerRegisterConfiguration
{
    public const MODULE_NAME = 'routerModule';

    /**
     * @inheritDoc
     */
    public static function createMessageHandlerFrom(AnnotatedFinding $annotationRegistration, InterfaceToCallRegistry $interfaceToCallRegistry): MessageHandlerBuilderWithParameterConverters
    {
        /** @var Router $annotation */
        $annotation = $annotationRegistration->getAnnotationForMethod();

        return RouterBuilder::create(
            AnnotatedDefinitionReference::getReferenceFor($annotationRegistration),
            $interfaceToCallRegistry->getFor($annotationRegistration->getClassName(), $annotationRegistration->getMethodName())
        )
            ->withEndpointId($annotation->getEndpointId())
            ->withInputChannelName($annotation->getInputChannelName())
            ->setResolutionRequired($annotation->isResolutionRequired);
    }

    /**
     * @inheritDoc
     */
    public static function getMessageHandlerAnnotation(): string
    {
        return Router::class;
    }

    public function getModulePackageName(): string
    {
        return ModulePackageList::CORE_PACKAGE;
    }
}
