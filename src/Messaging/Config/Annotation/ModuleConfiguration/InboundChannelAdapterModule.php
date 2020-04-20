<?php
declare(strict_types=1);


namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration;


use Ecotone\Messaging\Annotation\Scheduled;
use Ecotone\Messaging\Annotation\ModuleAnnotation;
use Ecotone\Messaging\Config\Annotation\AnnotationRegistration;
use Ecotone\Messaging\Endpoint\ConsumerLifecycleBuilder;
use Ecotone\Messaging\Endpoint\InboundChannelAdapter\InboundChannelAdapterBuilder;

/**
 * Class InboundChannelAdapterModule
 * @package Ecotone\Messaging\Config\Annotation\ModuleConfiguration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ModuleAnnotation()
 */
class InboundChannelAdapterModule extends ConsumerRegisterConfiguration
{
    /**
     * @inheritDoc
     */
    public static function getConsumerAnnotation(): string
    {
        return Scheduled::class;
    }

    /**
     * @inheritDoc
     */
    public static function createConsumerFrom(AnnotationRegistration $annotationRegistration): ConsumerLifecycleBuilder
    {
        /** @var Scheduled $annotation */
        $annotation = $annotationRegistration->getAnnotationForMethod();

        return InboundChannelAdapterBuilder::create($annotation->requestChannelName, $annotationRegistration->getReferenceName(), $annotationRegistration->getMethodName())
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