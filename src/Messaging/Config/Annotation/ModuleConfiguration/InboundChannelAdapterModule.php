<?php
declare(strict_types=1);


namespace SimplyCodedSoftware\Messaging\Config\Annotation\ModuleConfiguration;


use SimplyCodedSoftware\Messaging\Annotation\InboundChannelAdapter;
use SimplyCodedSoftware\Messaging\Config\Annotation\AnnotationRegistration;
use SimplyCodedSoftware\Messaging\Endpoint\ConsumerLifecycleBuilder;
use SimplyCodedSoftware\Messaging\Endpoint\InboundChannelAdapter\InboundChannelAdapterBuilder;

/**
 * Class InboundChannelAdapterModule
 * @package SimplyCodedSoftware\Messaging\Config\Annotation\ModuleConfiguration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InboundChannelAdapterModule extends ConsumerRegisterConfiguration
{
    /**
     * @inheritDoc
     */
    public static function getConsumerAnnotation(): string
    {
        return InboundChannelAdapter::class;
    }

    /**
     * @inheritDoc
     */
    public static function createConsumerFrom(AnnotationRegistration $annotationRegistration): ConsumerLifecycleBuilder
    {
        /** @var InboundChannelAdapter $annotation */
        $annotation = $annotationRegistration->getAnnotationForMethod();

        return InboundChannelAdapterBuilder::create($annotation->inputChannelName, $annotationRegistration->getReferenceName(), $annotationRegistration->getMethodName())
                    ->withEndpointId($annotation->endpointId)
                    ->withRequiredInterceptorNames($annotation->requiredInterceptorNames);
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return "inboundChannelAdapterModule";
    }
}