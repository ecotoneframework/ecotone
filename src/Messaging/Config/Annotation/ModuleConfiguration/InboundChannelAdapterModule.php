<?php
declare(strict_types=1);


namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration;


use Ecotone\AnnotationFinder\AnnotatedFinding;
use Ecotone\Messaging\Annotation\ModuleAnnotation;
use Ecotone\Messaging\Annotation\Scheduled;
use Ecotone\Messaging\Config\Annotation\AnnotatedDefinitionReference;
use Ecotone\Messaging\Config\Annotation\AnnotationRegistration;
use Ecotone\Messaging\Endpoint\ConsumerLifecycleBuilder;
use Ecotone\Messaging\Endpoint\InboundChannelAdapter\InboundChannelAdapterBuilder;

#[ModuleAnnotation]
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
    public static function createConsumerFrom(AnnotatedFinding $annotationRegistration): ConsumerLifecycleBuilder
    {
        /** @var Scheduled $annotation */
        $annotation = $annotationRegistration->getAnnotationForMethod();

        return InboundChannelAdapterBuilder::create($annotation->requestChannelName, AnnotatedDefinitionReference::getReferenceFor($annotationRegistration), $annotationRegistration->getMethodName())
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