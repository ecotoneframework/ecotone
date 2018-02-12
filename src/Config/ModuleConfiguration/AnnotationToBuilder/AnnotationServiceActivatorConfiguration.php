<?php

namespace SimplyCodedSoftware\Messaging\Config\ModuleConfiguration\AnnotationToBuilder;

use SimplyCodedSoftware\Messaging\Annotation\ServiceActivatorAnnotation;
use SimplyCodedSoftware\Messaging\Config\Configuration;
use SimplyCodedSoftware\Messaging\Config\ModuleConfiguration\AnnotationConfiguration;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilderWithParameterConverters;
use SimplyCodedSoftware\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;

/**
 * Class AnnotationServiceActivatorConfiguration
 * @package SimplyCodedSoftware\Messaging\Config\ModuleConfiguration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AnnotationServiceActivatorConfiguration extends MessageHandlerRegisterConfiguration implements AnnotationConfiguration
{
    /**
     * @inheritDoc
     */
    public function createMessageHandlerFrom(AnnotationRegistration $annotationRegistration): MessageHandlerBuilderWithParameterConverters
    {
        /** @var ServiceActivatorAnnotation $annotation */
        $annotation = $annotationRegistration->getAnnotation();

        return ServiceActivatorBuilder::create($annotationRegistration->getReferenceName(), $annotationRegistration->getMethodName())
                    ->withRequiredReply($annotation->requiresReply)
                    ->withInputMessageChannel($annotation->inputChannel)
                    ->withOutputChannel($annotation->outputChannel)
                    ->withConsumerName($annotationRegistration->getReferenceName());
    }

    /**
     * @inheritDoc
     */
    public function getMessageHandlerAnnotation(): string
    {
        return ServiceActivatorAnnotation::class;
    }
}