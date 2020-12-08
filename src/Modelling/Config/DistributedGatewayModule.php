<?php


namespace Ecotone\Modelling\Config;
use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Annotation\ModuleAnnotation;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\NoExternalConfigurationModule;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeadersBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadBuilder;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Modelling\DistributeGateway;
use Ecotone\Modelling\MessageHandling\Distribution\DistributedMessageHandler;

#[ModuleAnnotation]
class DistributedGatewayModule extends NoExternalConfigurationModule implements AnnotationModule
{
    public static function create(AnnotationFinder $annotationRegistrationService): static
    {
        return new self();
    }

    public function prepare(Configuration $configuration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService): void
    {
        $configuration->registerGatewayBuilder(
            GatewayProxyBuilder::create(DistributeGateway::class, DistributeGateway::class, "distribute", DistributeGateway::DISTRIBUTED_CHANNEL)
                ->withMessageConverters([
                    GatewayPayloadBuilder::create("payload"),
                    GatewayHeadersBuilder::create("metadata"),
                    GatewayHeaderBuilder::create("payloadType", DistributeGateway::DISTRIBUTED_PAYLOAD_TYPE),
                    GatewayHeaderBuilder::create("routingKey", DistributeGateway::DISTRIBUTED_ROUTING_KEY),
                    GatewayHeaderBuilder::create("mediaType", MessageHeaders::CONTENT_TYPE)
                ])
        );
        $configuration->registerMessageHandler(
            ServiceActivatorBuilder::createWithDirectReference(new DistributedMessageHandler(), "handle")
                ->withMethodParameterConverters([

                ])
        );
    }

    public function canHandle($extensionObject): bool
    {
        return false;
    }
}