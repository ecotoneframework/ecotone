<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Config\ModuleConfiguration\AnnotationToBuilder;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\ServiceActivatorAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Config\Configuration;
use SimplyCodedSoftware\IntegrationMessaging\Config\ModuleConfiguration\AnnotationConfiguration;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilderWithParameterConverters;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ServiceActivator\ServiceActivatorBuilder;

/**
 * Class AnnotationServiceActivatorConfiguration
 * @package SimplyCodedSoftware\IntegrationMessaging\Config\ModuleConfiguration
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