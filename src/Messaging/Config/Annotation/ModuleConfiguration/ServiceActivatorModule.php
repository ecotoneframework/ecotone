<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration;

use Ecotone\AnnotationFinder\AnnotatedFinding;
use Ecotone\Messaging\Attribute\ModuleAnnotation;
use Ecotone\Messaging\Attribute\ServiceActivator;
use Ecotone\Messaging\Config\Annotation\AnnotatedDefinitionReference;
use Ecotone\Messaging\Handler\MessageHandlerBuilderWithParameterConverters;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;

#[ModuleAnnotation]
class ServiceActivatorModule extends MessageHandlerRegisterConfiguration
{
    /**
     * @inheritDoc
     */
    public static function createMessageHandlerFrom(AnnotatedFinding $annotationRegistration): MessageHandlerBuilderWithParameterConverters
    {
        /** @var ServiceActivator $annotation */
        $annotation = $annotationRegistration->getAnnotationForMethod();

        return ServiceActivatorBuilder::create(AnnotatedDefinitionReference::getReferenceFor($annotationRegistration), $annotationRegistration->getMethodName())
            ->withEndpointId($annotation->getEndpointId())
            ->withRequiredReply($annotation->isRequiresReply())
            ->withOutputMessageChannel($annotation->getOutputChannelName())
            ->withInputChannelName($annotation->getInputChannelName())
            ->withRequiredInterceptorNames($annotation->getRequiredInterceptorNames());
    }

    /**
     * @inheritDoc
     */
    public static function getMessageHandlerAnnotation(): string
    {
        return ServiceActivator::class;
    }

    public function getModulePackageName(): string
    {
        return CoreModule::NAME;
    }
}
