<?php

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration;

use Ecotone\AnnotationFinder\AnnotatedFinding;
use Ecotone\Messaging\Annotation\ModuleAnnotation;
use Ecotone\Messaging\Annotation\Splitter;
use Ecotone\Messaging\Annotation\Transformer;
use Ecotone\Messaging\Config\Annotation\AnnotatedDefinitionReference;
use Ecotone\Messaging\Config\Annotation\AnnotationRegistration;
use Ecotone\Messaging\Handler\MessageHandlerBuilderWithParameterConverters;
use Ecotone\Messaging\Handler\Splitter\SplitterBuilder;

/**
 * Class AnnotationTransformerConfiguration
 * @package Ecotone\Messaging\Config\Annotation
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @ModuleAnnotation()
 */
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
                ->withEndpointId($annotation->endpointId)
                ->withInputChannelName($annotation->inputChannelName)
                ->withOutputMessageChannel($annotation->outputChannelName)
                ->withRequiredInterceptorNames($annotation->requiredInterceptorNames);
    }

    /**
     * @inheritDoc
     */
    public static function getMessageHandlerAnnotation(): string
    {
        return Splitter::class;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return self::MODULE_NAME;
    }
}