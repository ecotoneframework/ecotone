<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\ModuleAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ServiceActivator;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationModule;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationRegistration;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilderWithParameterConverters;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ServiceActivator\ServiceActivatorBuilder;

/**
 * Class AnnotationServiceActivatorConfiguration
 * @package SimplyCodedSoftware\IntegrationMessaging\Config\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ModuleAnnotation()
 */
class ServiceActivatorModule extends MessageHandlerRegisterConfiguration
{
    public const MODULE_NAME = "serviceActivatorModule";

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
        /** @var ServiceActivator $annotation */
        $annotation = $annotationRegistration->getAnnotationForMethod();

        return ServiceActivatorBuilder::create($annotationRegistration->getReferenceName(), $annotationRegistration->getMethodName())
            ->withEndpointId($annotation->endpointId)
            ->withRequiredReply($annotation->requiresReply)
            ->withOutputMessageChannel($annotation->outputChannelName)
            ->withInputChannelName($annotation->inputChannelName);
    }

    /**
     * @inheritDoc
     */
    public static function getMessageHandlerAnnotation(): string
    {
        return ServiceActivator::class;
    }
}