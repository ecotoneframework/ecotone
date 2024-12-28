<?php

namespace Ecotone\Modelling\Config;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Attribute\ModuleAnnotation;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\NoExternalConfigurationModule;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Gateway\MessagingEntrypoint;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeadersBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadBuilder;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\AllHeadersBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\HeaderBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\ReferenceBuilder;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\DistributionEntrypoint;
use Ecotone\Modelling\EventBus;
use Ecotone\Modelling\MessageHandling\Distribution\DistributedMessageHandler;

#[ModuleAnnotation]
/**
 * licence Apache-2.0
 */
class DistributedGatewayModule extends NoExternalConfigurationModule implements AnnotationModule
{
    private array $distributedEventHandlerRoutingKeys;
    private array $distributedCommandHandlerRoutingKeys;

    public function __construct(array $distributedEventHandlerRoutingKeys, array $distributedCommandHandlerRoutingKeys)
    {
        $this->distributedEventHandlerRoutingKeys   = $distributedEventHandlerRoutingKeys;
        $this->distributedCommandHandlerRoutingKeys = $distributedCommandHandlerRoutingKeys;
    }

    public static function create(AnnotationFinder $annotationFinder, InterfaceToCallRegistry $interfaceToCallRegistry): static
    {
        return new self(self::getDistributedEventHandlerRoutingKeys($annotationFinder, $interfaceToCallRegistry), self::getDistributedCommandHandlerRoutingKeys($annotationFinder, $interfaceToCallRegistry));
    }

    public static function getDistributedCommandHandlerRoutingKeys(AnnotationFinder $annotationFinder, InterfaceToCallRegistry $interfaceToCallRegistry): array
    {
        $routingKeys = array_merge(
            MessageHandlerRoutingModule::getCommandBusByNamesMapping($annotationFinder, $interfaceToCallRegistry, true),
            MessageHandlerRoutingModule::getCommandBusByObjectMapping($annotationFinder, $interfaceToCallRegistry, true)
        );

        return array_keys($routingKeys);
    }

    public static function getDistributedEventHandlerRoutingKeys(AnnotationFinder $annotationFinder, InterfaceToCallRegistry $interfaceToCallRegistry): array
    {
        $routingKeys = array_merge(
            MessageHandlerRoutingModule::getEventBusByNamesMapping($annotationFinder, $interfaceToCallRegistry, true),
            MessageHandlerRoutingModule::getEventBusByObjectsMapping($annotationFinder, $interfaceToCallRegistry, true)
        );

        return array_keys($routingKeys);
    }

    public function prepare(Configuration $messagingConfiguration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        $messagingConfiguration->registerGatewayBuilder(
            GatewayProxyBuilder::create(DistributionEntrypoint::class, DistributionEntrypoint::class, 'distribute', DistributionEntrypoint::DISTRIBUTED_CHANNEL)
                ->withParameterConverters([
                    GatewayPayloadBuilder::create('payload'),
                    GatewayHeadersBuilder::create('metadata'),
                    GatewayHeaderBuilder::create('payloadType', DistributionEntrypoint::DISTRIBUTED_PAYLOAD_TYPE),
                    GatewayHeaderBuilder::create('routingKey', DistributionEntrypoint::DISTRIBUTED_ROUTING_KEY),
                    GatewayHeaderBuilder::create('mediaType', MessageHeaders::CONTENT_TYPE),
                ])
        );
        $messagingConfiguration->registerGatewayBuilder(
            GatewayProxyBuilder::create(DistributionEntrypoint::class, DistributionEntrypoint::class, 'distributeMessage', DistributionEntrypoint::DISTRIBUTED_CHANNEL)
                ->withParameterConverters([
                    GatewayPayloadBuilder::create('payload'),
                    GatewayHeadersBuilder::create('metadata'),
                    GatewayHeaderBuilder::create('mediaType', MessageHeaders::CONTENT_TYPE),
                ])
        );
        $messagingConfiguration->registerServiceDefinition(
            DistributedMessageHandler::class,
            new Definition(DistributedMessageHandler::class, [
                $this->distributedEventHandlerRoutingKeys,
                $this->distributedCommandHandlerRoutingKeys,
            ])
        );
        $messagingConfiguration->registerMessageHandler(
            ServiceActivatorBuilder::create(DistributedMessageHandler::class, $interfaceToCallRegistry->getFor(DistributedMessageHandler::class, 'handle'))
                ->withInputChannelName(DistributionEntrypoint::DISTRIBUTED_CHANNEL)
                ->withMethodParameterConverters([
                    PayloadBuilder::create('payload'),
                    AllHeadersBuilder::createWith('metadata'),
                    HeaderBuilder::create('payloadType', DistributionEntrypoint::DISTRIBUTED_PAYLOAD_TYPE),
                    HeaderBuilder::create('routingKey', DistributionEntrypoint::DISTRIBUTED_ROUTING_KEY),
                    HeaderBuilder::create('contentType', MessageHeaders::CONTENT_TYPE),
                    ReferenceBuilder::create('commandBus', CommandBus::class),
                    ReferenceBuilder::create('eventBus', EventBus::class),
                    ReferenceBuilder::create('messagingEntrypoint', MessagingEntrypoint::class),
                ])
        );
    }

    public function canHandle($extensionObject): bool
    {
        return false;
    }

    public function getModulePackageName(): string
    {
        return ModulePackageList::CORE_PACKAGE;
    }
}
