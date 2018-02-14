<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\Annotation;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\ModuleConfigurationAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ServiceActivatorAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationConfiguration;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilderWithParameterConverters;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ServiceActivator\ServiceActivatorBuilder;

/**
 * Class AnnotationServiceActivatorConfiguration
 * @package SimplyCodedSoftware\IntegrationMessaging\Config\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ModuleConfigurationAnnotation(moduleName="service-activator-configuration")
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