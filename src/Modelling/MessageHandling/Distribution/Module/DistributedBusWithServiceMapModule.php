<?php

declare(strict_types=1);

namespace Ecotone\Modelling\MessageHandling\Distribution\Module;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Lite\Test\TestConfiguration;
use Ecotone\Messaging\Attribute\ModuleAnnotation;
use Ecotone\Messaging\Channel\MessageChannelBuilder;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ExtensionObjectResolver;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\NoExternalConfigurationModule;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeadersBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderValueBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadBuilder;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\HeaderBuilder;
use Ecotone\Messaging\Handler\Router\RouterBuilder;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\LicensingException;
use Ecotone\Modelling\Api\Distribution\DistributedBusHeader;
use Ecotone\Modelling\Api\Distribution\DistributedServiceMap;
use Ecotone\Modelling\DistributedBus;
use Ecotone\Modelling\MessageHandling\Distribution\DistributedOutboundRouter;

#[ModuleAnnotation]
/**
 * licence Enterprise
 */
final class DistributedBusWithServiceMapModule extends NoExternalConfigurationModule implements AnnotationModule
{
    public static function create(AnnotationFinder $annotationRegistrationService, InterfaceToCallRegistry $interfaceToCallRegistry): static
    {
        return new self();
    }

    public function prepare(Configuration $messagingConfiguration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        /** @var DistributedServiceMap[] $distributedServiceMaps */
        $distributedServiceMaps = ExtensionObjectResolver::resolve(DistributedServiceMap::class, $extensionObjects);
        $messageChannels = ExtensionObjectResolver::resolve(MessageChannelBuilder::class, $extensionObjects);
        $applicationConfiguration = ExtensionObjectResolver::resolveUnique(ServiceConfiguration::class, $extensionObjects, ServiceConfiguration::createWithDefaults());

        foreach ($distributedServiceMaps as $distributedServiceMap) {
            if (! $messagingConfiguration->isRunningForEnterpriseLicence()) {
                throw LicensingException::create('Distributed Bus with Service Map is available only as part of Ecotone Enterprise.');
            }

            foreach ($distributedServiceMap->getServiceMapping() as $serviceName => $channelName) {
                if (! $this->isMessageChannelAvailable($messageChannels, $channelName)) {
                    throw ConfigurationException::create("Service Map has Service {$serviceName} mapped to channel {$channelName} but it is not available in message channels. Have you forgot to register it?");
                }
            }

            $this->registerDistributedBus(
                $applicationConfiguration,
                $messagingConfiguration,
                $distributedServiceMap,
                $interfaceToCallRegistry,
            );
        }
    }

    public function canHandle($extensionObject): bool
    {
        return $extensionObject instanceof TestConfiguration || $extensionObject instanceof ServiceConfiguration || $extensionObject instanceof DistributedServiceMap || $extensionObject instanceof MessageChannelBuilder;
    }

    public function getModulePackageName(): string
    {
        return ModulePackageList::CORE_PACKAGE;
    }

    private function registerDistributedBus(ServiceConfiguration $applicationConfiguration, Configuration $configuration, DistributedServiceMap $distributedServiceMap, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        Assert::isFalse($applicationConfiguration->getServiceName() === ServiceConfiguration::DEFAULT_SERVICE_NAME, 'Service name must be provided in ServiceConfiguration to make use of Distributed Bus', true);

        $distributedChannelName = $distributedServiceMap->getReferenceName() . '_outboundDistribution';
        $outboundRouterReference = $distributedServiceMap->getReferenceName() . '_' . DistributedOutboundRouter::class;

        $configuration->registerServiceDefinition(
            $outboundRouterReference,
            Definition::createFor(
                DistributedOutboundRouter::class,
                [
                    $distributedServiceMap,
                    $applicationConfiguration->getServiceName(),
                ]
            )
        );

        $configuration
            ->registerMessageHandler(
                RouterBuilder::create(
                    $outboundRouterReference,
                    $interfaceToCallRegistry->getFor(DistributedOutboundRouter::class, 'route')
                )
                    ->withInputChannelName($distributedChannelName)
                    ->withMethodParameterConverters(
                        [
                            HeaderBuilder::create('payloadType', DistributedBusHeader::DISTRIBUTED_PAYLOAD_TYPE),
                            HeaderBuilder::createOptional('targetedServiceName', DistributedBusHeader::DISTRIBUTED_TARGET_SERVICE_NAME),
                            HeaderBuilder::create('routingKey', DistributedBusHeader::DISTRIBUTED_ROUTING_KEY),
                        ]
                    )
                    ->setResolutionRequired(false)
            )
            ->registerGatewayBuilder(
                GatewayProxyBuilder::create($distributedServiceMap->getReferenceName(), DistributedBus::class, 'sendCommand', $distributedChannelName)
                    ->withParameterConverters(
                        [
                            GatewayPayloadBuilder::create('command'),
                            GatewayHeadersBuilder::create('metadata'),
                            GatewayHeaderBuilder::create('sourceMediaType', MessageHeaders::CONTENT_TYPE),
                            GatewayHeaderBuilder::create('routingKey', DistributedBusHeader::DISTRIBUTED_ROUTING_KEY),
                            GatewayHeaderBuilder::create('targetServiceName', DistributedBusHeader::DISTRIBUTED_TARGET_SERVICE_NAME),
                            GatewayHeaderValueBuilder::create(DistributedBusHeader::DISTRIBUTED_SOURCE_SERVICE_NAME, $applicationConfiguration->getServiceName()),
                            GatewayHeaderValueBuilder::create(DistributedBusHeader::DISTRIBUTED_PAYLOAD_TYPE, 'command'),
                            GatewayHeaderValueBuilder::create(MessageHeaders::ROUTING_SLIP, DistributedBusHeader::DISTRIBUTED_ROUTING_SLIP_VALUE),
                        ]
                    )
                    ->withEndpointAnnotations($distributedServiceMap->getDistributedBusAnnotations())
            )
            ->registerGatewayBuilder(
                GatewayProxyBuilder::create($distributedServiceMap->getReferenceName(), DistributedBus::class, 'convertAndSendCommand', $distributedChannelName)
                    ->withParameterConverters(
                        [
                            GatewayPayloadBuilder::create('command'),
                            GatewayHeadersBuilder::create('metadata'),
                            GatewayHeaderBuilder::create('routingKey', DistributedBusHeader::DISTRIBUTED_ROUTING_KEY),
                            GatewayHeaderBuilder::create('targetServiceName', DistributedBusHeader::DISTRIBUTED_TARGET_SERVICE_NAME),
                            GatewayHeaderValueBuilder::create(DistributedBusHeader::DISTRIBUTED_SOURCE_SERVICE_NAME, $applicationConfiguration->getServiceName()),
                            GatewayHeaderValueBuilder::create(DistributedBusHeader::DISTRIBUTED_PAYLOAD_TYPE, 'command'),
                            GatewayHeaderValueBuilder::create(MessageHeaders::ROUTING_SLIP, DistributedBusHeader::DISTRIBUTED_ROUTING_SLIP_VALUE),
                        ]
                    )
                    ->withEndpointAnnotations($distributedServiceMap->getDistributedBusAnnotations())
            )
            ->registerGatewayBuilder(
                GatewayProxyBuilder::create($distributedServiceMap->getReferenceName(), DistributedBus::class, 'publishEvent', $distributedChannelName)
                    ->withParameterConverters(
                        [
                            GatewayPayloadBuilder::create('event'),
                            GatewayHeadersBuilder::create('metadata'),
                            GatewayHeaderBuilder::create('sourceMediaType', MessageHeaders::CONTENT_TYPE),
                            GatewayHeaderBuilder::create('routingKey', DistributedBusHeader::DISTRIBUTED_ROUTING_KEY),
                            GatewayHeaderValueBuilder::create(DistributedBusHeader::DISTRIBUTED_SOURCE_SERVICE_NAME, $applicationConfiguration->getServiceName()),
                            GatewayHeaderValueBuilder::create(DistributedBusHeader::DISTRIBUTED_PAYLOAD_TYPE, 'event'),
                            GatewayHeaderValueBuilder::create(MessageHeaders::ROUTING_SLIP, DistributedBusHeader::DISTRIBUTED_ROUTING_SLIP_VALUE),
                        ]
                    )
                    ->withEndpointAnnotations($distributedServiceMap->getDistributedBusAnnotations())
            )
            ->registerGatewayBuilder(
                GatewayProxyBuilder::create($distributedServiceMap->getReferenceName(), DistributedBus::class, 'convertAndPublishEvent', $distributedChannelName)
                    ->withParameterConverters(
                        [
                            GatewayPayloadBuilder::create('event'),
                            GatewayHeadersBuilder::create('metadata'),
                            GatewayHeaderBuilder::create('routingKey', DistributedBusHeader::DISTRIBUTED_ROUTING_KEY),
                            GatewayHeaderValueBuilder::create(DistributedBusHeader::DISTRIBUTED_SOURCE_SERVICE_NAME, $applicationConfiguration->getServiceName()),
                            GatewayHeaderValueBuilder::create(DistributedBusHeader::DISTRIBUTED_PAYLOAD_TYPE, 'event'),
                            GatewayHeaderValueBuilder::create(MessageHeaders::ROUTING_SLIP, DistributedBusHeader::DISTRIBUTED_ROUTING_SLIP_VALUE),
                        ]
                    )
                    ->withEndpointAnnotations($distributedServiceMap->getDistributedBusAnnotations())
            )
            ->registerGatewayBuilder(
                GatewayProxyBuilder::create($distributedServiceMap->getReferenceName(), DistributedBus::class, 'sendMessage', $distributedChannelName)
                    ->withParameterConverters(
                        [
                            GatewayPayloadBuilder::create('payload'),
                            GatewayHeadersBuilder::create('metadata'),
                            GatewayHeaderBuilder::create('sourceMediaType', MessageHeaders::CONTENT_TYPE),
                            GatewayHeaderBuilder::create('targetChannelName', DistributedBusHeader::DISTRIBUTED_ROUTING_KEY),
                            GatewayHeaderBuilder::create('targetServiceName', DistributedBusHeader::DISTRIBUTED_TARGET_SERVICE_NAME),
                            GatewayHeaderValueBuilder::create(DistributedBusHeader::DISTRIBUTED_SOURCE_SERVICE_NAME, $applicationConfiguration->getServiceName()),
                            GatewayHeaderValueBuilder::create(DistributedBusHeader::DISTRIBUTED_PAYLOAD_TYPE, 'message'),
                            GatewayHeaderValueBuilder::create(MessageHeaders::ROUTING_SLIP, DistributedBusHeader::DISTRIBUTED_ROUTING_SLIP_VALUE),
                        ]
                    )
                    ->withEndpointAnnotations($distributedServiceMap->getDistributedBusAnnotations())
            );
    }

    public function isMessageChannelAvailable(array $messageChannels, mixed $channelName): bool
    {
        foreach ($messageChannels as $messageChannel) {
            if ($messageChannel->getMessageChannelName() === $channelName) {
                return true;
            }
        }

        return false;
    }
}
