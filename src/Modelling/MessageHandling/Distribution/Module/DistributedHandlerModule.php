<?php

namespace Ecotone\Modelling\MessageHandling\Distribution\Module;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\Distributed;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\ModuleAnnotation;
use Ecotone\Api\ExtensionObject\ModulePackageList;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\Gateway\CommandBus;
use Ecotone\Api\Gateway\DistributedBusHeader;
use Ecotone\Api\Gateway\EventBus;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ExtensionObjectResolver;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Gateway\MessagingEntrypointService;
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
use Ecotone\Modelling\Config\Routing\BusRoutingMapBuilder;
use Ecotone\Modelling\MessageHandling\Distribution\DistributedMessageHandler;
use Ecotone\Modelling\MessageHandling\Distribution\DistributionEntrypoint;

#[ModuleAnnotation]
/**
 * licence Apache-2.0
 */
final class DistributedHandlerModule implements AnnotationModule
{
    public function __construct(
        private BusRoutingMapBuilder $commandBusDistributedRoutes,
        private BusRoutingMapBuilder $eventBusDistributedRoutes,
    ) {
    }

    public static function create(AnnotationFinder $annotationFinder, InterfaceToCallRegistry $interfaceToCallRegistry): static
    {
        $commandBusDistributedRoutes = new BusRoutingMapBuilder();
        foreach ($annotationFinder->findAnnotatedMethods(CommandHandler::class) as $registration) {
            if ($registration->hasAnnotation(Distributed::class)) {
                $commandBusDistributedRoutes->addRoutesFromAnnotatedFinding($registration, $interfaceToCallRegistry);
            }
        }

        $eventBusDistributedRoutes = new BusRoutingMapBuilder();
        foreach ($annotationFinder->findAnnotatedMethods(EventHandler::class) as $registration) {
            if ($registration->hasAnnotation(Distributed::class)) {
                $eventBusDistributedRoutes->addRoutesFromAnnotatedFinding($registration, $interfaceToCallRegistry);
            }
        }
        return new self($commandBusDistributedRoutes, $eventBusDistributedRoutes);
    }

    /**
     * @return array<string>
     */
    public function getDistributedEventHandlerRoutes(): array
    {
        return $this->eventBusDistributedRoutes->getRoutingKeys();
    }

    public function getModuleExtensions(ServiceConfiguration $serviceConfiguration, array $serviceExtensions): array
    {
        return [$this];
    }

    public function prepare(Configuration $messagingConfiguration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        $serviceConfiguration = ExtensionObjectResolver::resolveUnique(ServiceConfiguration::class, $extensionObjects, ServiceConfiguration::createWithDefaults());

        $messagingConfiguration->registerGatewayBuilder(
            GatewayProxyBuilder::create(DistributionEntrypoint::class, DistributionEntrypoint::class, 'distribute', DistributedBusHeader::DISTRIBUTED_ROUTING_SLIP_VALUE)
                ->withParameterConverters([
                    GatewayPayloadBuilder::create('payload'),
                    GatewayHeadersBuilder::create('metadata'),
                    GatewayHeaderBuilder::create('payloadType', DistributedBusHeader::DISTRIBUTED_PAYLOAD_TYPE),
                    GatewayHeaderBuilder::create('routingKey', DistributedBusHeader::DISTRIBUTED_ROUTING_KEY),
                    GatewayHeaderBuilder::create('mediaType', MessageHeaders::CONTENT_TYPE),
                ])
        );
        $messagingConfiguration->registerGatewayBuilder(
            GatewayProxyBuilder::create(DistributionEntrypoint::class, DistributionEntrypoint::class, 'distributeMessage', DistributedBusHeader::DISTRIBUTED_ROUTING_SLIP_VALUE)
                ->withParameterConverters([
                    GatewayPayloadBuilder::create('payload'),
                    GatewayHeadersBuilder::create('metadata'),
                    GatewayHeaderBuilder::create('mediaType', MessageHeaders::CONTENT_TYPE),
                ])
        );
        $messagingConfiguration->registerServiceDefinition(
            DistributedMessageHandler::class,
            new Definition(DistributedMessageHandler::class, [
                $this->eventBusDistributedRoutes->compile(),
                $this->commandBusDistributedRoutes->compile(),
                $serviceConfiguration->serviceName,
            ])
        );
        $messagingConfiguration->registerMessageHandler(
            ServiceActivatorBuilder::create(DistributedMessageHandler::class, $interfaceToCallRegistry->getFor(DistributedMessageHandler::class, 'handle'))
                ->withInputChannelName(DistributedBusHeader::DISTRIBUTED_ROUTING_SLIP_VALUE)
                ->withMethodParameterConverters([
                    PayloadBuilder::create('payload'),
                    AllHeadersBuilder::createWith('metadata'),
                    HeaderBuilder::create('payloadType', DistributedBusHeader::DISTRIBUTED_PAYLOAD_TYPE),
                    HeaderBuilder::create('routingKey', DistributedBusHeader::DISTRIBUTED_ROUTING_KEY),
                    HeaderBuilder::create('contentType', MessageHeaders::CONTENT_TYPE),
                    HeaderBuilder::createOptional('targetedServiceName', DistributedBusHeader::DISTRIBUTED_TARGET_SERVICE_NAME),
                    ReferenceBuilder::create('commandBus', CommandBus::class),
                    ReferenceBuilder::create('eventBus', EventBus::class),
                    ReferenceBuilder::create('messagingEntrypoint', MessagingEntrypointService::class),
                ])
        );
    }

    public function getModulePackageName(): string
    {
        return ModulePackageList::CORE_PACKAGE;
    }
}
