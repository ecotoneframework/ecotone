<?php

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration;

use Ecotone\AnnotationFinder\AnnotatedFinding;
use Ecotone\Messaging\Attribute\ModuleAnnotation;
use Ecotone\Messaging\Attribute\Splitter;
use Ecotone\Messaging\Attribute\Transformer;
use Ecotone\Messaging\Config\Annotation\AnnotatedDefinitionReference;
use Ecotone\Messaging\Config\Annotation\AnnotationRegistration;
use Ecotone\Messaging\Handler\MessageHandlerBuilderWithParameterConverters;
use Ecotone\Messaging\Handler\Splitter\SplitterBuilder;

#[ModuleAnnotation]
class SplitterModule extends MessageHandlerRegisterConfiguration
{
    public const MODULE_NAME = "splitterModule";

    /**
     * @inheritDoc
     */
    public static function createMessageHandlerFrom(AnnotatedFinding $annotationRegistration): MessageHandlerBuilderWithParameterConverters
    {
        /** @var Transformer $annotation */
        $annotation = $annotationRegistration->getAnnotationForMethod();

        return
            SplitterBuilder::create(
                AnnotatedDefinitionReference::getReferenceFor($annotationRegistration),
                $annotationRegistration->getMethodName()
            )
                ->withEndpointId($annotation->getEndpointId())
                ->withInputChannelName($annotation->getInputChannelName())
                ->withOutputMessageChannel($annotation->getOutputChannelName())
                ->withRequiredInterceptorNames($annotation->getRequiredInterceptorNames());
    }

    /**
     * @inheritDoc
     */
    public static function getMessageHandlerAnnotation(): string
    {
        return Splitter::class;
    }
}