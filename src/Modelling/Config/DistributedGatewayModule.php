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
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\AllHeadersBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\HeaderBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\ReferenceBuilder;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\DistributedGateway;
use Ecotone\Modelling\EventBus;
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
            GatewayProxyBuilder::create(DistributedGateway::class, DistributedGateway::class, "distribute", DistributedGateway::DISTRIBUTED_CHANNEL)
                ->withParameterConverters([
                    GatewayPayloadBuilder::create("payload"),
                    GatewayHeadersBuilder::create("metadata"),
                    GatewayHeaderBuilder::create("payloadType", DistributedGateway::DISTRIBUTED_PAYLOAD_TYPE),
                    GatewayHeaderBuilder::create("routingKey", DistributedGateway::DISTRIBUTED_ROUTING_KEY),
                    GatewayHeaderBuilder::create("mediaType", MessageHeaders::CONTENT_TYPE)
                ])
        );
        $configuration->registerMessageHandler(
            ServiceActivatorBuilder::createWithDirectReference(new DistributedMessageHandler(), "handle")
                ->withInputChannelName(DistributedGateway::DISTRIBUTED_CHANNEL)
                ->withMethodParameterConverters([
                    PayloadBuilder::create("payload"),
                    AllHeadersBuilder::createWith("metadata"),
                    HeaderBuilder::create("payloadType", DistributedGateway::DISTRIBUTED_PAYLOAD_TYPE),
                    HeaderBuilder::create("routingKey", DistributedGateway::DISTRIBUTED_ROUTING_KEY),
                    HeaderBuilder::create("contentType", MessageHeaders::CONTENT_TYPE),
                    ReferenceBuilder::create("commandBus", CommandBus::class),
                    ReferenceBuilder::create("eventBus", EventBus::class),
                ])
        );
    }

    public function canHandle($extensionObject): bool
    {
        return false;
    }
}