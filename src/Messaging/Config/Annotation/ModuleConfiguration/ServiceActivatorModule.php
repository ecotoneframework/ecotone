<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Config\Annotation\ModuleConfiguration;

use SimplyCodedSoftware\Messaging\Annotation\ModuleAnnotation;
use SimplyCodedSoftware\Messaging\Annotation\ServiceActivator;
use SimplyCodedSoftware\Messaging\Config\Annotation\AnnotationModule;
use SimplyCodedSoftware\Messaging\Config\Annotation\AnnotationRegistration;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilderWithParameterConverters;
use SimplyCodedSoftware\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;

/**
 * Class AnnotationServiceActivatorConfiguration
 * @package SimplyCodedSoftware\Messaging\Config\Annotation
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