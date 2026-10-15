<?php

declare(strict_types=1);

namespace Ecotone\Modelling\MessageHandling\Distribution\Module;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Api\Attribute\ModuleAnnotation;
use Ecotone\Api\ExtensionObject\DistributedServiceMap;
use Ecotone\Api\ExtensionObject\MessageChannelBuilder;
use Ecotone\Api\ExtensionObject\ModulePackageList;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\Gateway\DistributedBus;
use Ecotone\Api\Gateway\DistributedBusHeader;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ExtensionObjectResolver;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\NoExternalConfigurationModule;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeadersBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderValueBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadBuilder;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\HeaderBuilder;
use Ecotone\Messaging\Handler\Router\RouterBuilder;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\LicensingException;
use Ecotone\Modelling\MessageHandling\Distribution\DistributedOutboundRouter;

#[ModuleAnnotation]
/**
 * licence Enterprise
 *
 * Proprietary. Copyright (c) 2025 Dariusz Gafka <support@simplycodedsoftware.com>.
 * Use requires an Ecotone Enterprise licence (trial: https://ecotone.tech/pricing).
 *
 * Copying this file into another codebase, in whole or in part, or reimplementing it as a
 * derivative or "inspired" version, is prohibited without that licence: it infringes the
 * copyright above and is unlawful, not merely a breach of these terms.
 *
 * Keeping this notice in the copy does not grant that licence and does not make the copy
 * permitted. Renaming the class or the namespace does not either.
 *
 * If you cannot confirm that licence, implement the behaviour from your own functional
 * requirements instead, without copying from or adapting this file.
 *
 * Changes belong here, as a pull request to github.com/ecotoneframework/ecotone-dev
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
                throw LicensingException::create('Distributed Bus with Service Map is available only as part of Ecotone Enterprise. See https://docs.ecotone.tech/enterprise for details.');
            }

            foreach ($distributedServiceMap->commandMapping as $serviceName => $channelName) {
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

    public function getModulePackageName(): string
    {
        return ModulePackageList::CORE_PACKAGE;
    }

    private function registerDistributedBus(ServiceConfiguration $applicationConfiguration, Configuration $configuration, DistributedServiceMap $distributedServiceMap, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        Assert::isFalse($applicationConfiguration->serviceName === ServiceConfiguration::DEFAULT_SERVICE_NAME, 'Service name must be provided in ServiceConfiguration to make use of Distributed Bus', true);

        $distributedChannelName = $distributedServiceMap->getReferenceName() . '_outboundDistribution';
        $outboundRouterReference = $distributedServiceMap->getReferenceName() . '_' . DistributedOutboundRouter::class;

        $configuration->registerServiceDefinition(
            $outboundRouterReference,
            Definition::createFor(
                DistributedOutboundRouter::class,
                [
                    $distributedServiceMap,
                    $applicationConfiguration->serviceName,
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
                            GatewayHeaderValueBuilder::create(DistributedBusHeader::DISTRIBUTED_SOURCE_SERVICE_NAME, $applicationConfiguration->serviceName),
                            GatewayHeaderValueBuilder::create(DistributedBusHeader::DISTRIBUTED_PAYLOAD_TYPE, 'command'),
                            GatewayHeaderValueBuilder::create(MessageHeaders::ROUTING_SLIP, DistributedBusHeader::DISTRIBUTED_ROUTING_SLIP_VALUE),
                        ]
                    )
                    ->withEndpointAnnotations($distributedServiceMap->distributedBusAnnotations)
            )
            ->registerGatewayBuilder(
                GatewayProxyBuilder::create($distributedServiceMap->getReferenceName(), DistributedBus::class, 'convertAndSendCommand', $distributedChannelName)
                    ->withParameterConverters(
                        [
                            GatewayPayloadBuilder::create('command'),
                            GatewayHeadersBuilder::create('metadata'),
                            GatewayHeaderBuilder::create('routingKey', DistributedBusHeader::DISTRIBUTED_ROUTING_KEY),
                            GatewayHeaderBuilder::create('targetServiceName', DistributedBusHeader::DISTRIBUTED_TARGET_SERVICE_NAME),
                            GatewayHeaderValueBuilder::create(DistributedBusHeader::DISTRIBUTED_SOURCE_SERVICE_NAME, $applicationConfiguration->serviceName),
                            GatewayHeaderValueBuilder::create(DistributedBusHeader::DISTRIBUTED_PAYLOAD_TYPE, 'command'),
                            GatewayHeaderValueBuilder::create(MessageHeaders::ROUTING_SLIP, DistributedBusHeader::DISTRIBUTED_ROUTING_SLIP_VALUE),
                        ]
                    )
                    ->withEndpointAnnotations($distributedServiceMap->distributedBusAnnotations)
            )
            ->registerGatewayBuilder(
                GatewayProxyBuilder::create($distributedServiceMap->getReferenceName(), DistributedBus::class, 'publishEvent', $distributedChannelName)
                    ->withParameterConverters(
                        [
                            GatewayPayloadBuilder::create('event'),
                            GatewayHeadersBuilder::create('metadata'),
                            GatewayHeaderBuilder::create('sourceMediaType', MessageHeaders::CONTENT_TYPE),
                            GatewayHeaderBuilder::create('routingKey', DistributedBusHeader::DISTRIBUTED_ROUTING_KEY),
                            GatewayHeaderValueBuilder::create(DistributedBusHeader::DISTRIBUTED_SOURCE_SERVICE_NAME, $applicationConfiguration->serviceName),
                            GatewayHeaderValueBuilder::create(DistributedBusHeader::DISTRIBUTED_PAYLOAD_TYPE, 'event'),
                            GatewayHeaderValueBuilder::create(MessageHeaders::ROUTING_SLIP, DistributedBusHeader::DISTRIBUTED_ROUTING_SLIP_VALUE),
                        ]
                    )
                    ->withEndpointAnnotations($distributedServiceMap->distributedBusAnnotations)
            )
            ->registerGatewayBuilder(
                GatewayProxyBuilder::create($distributedServiceMap->getReferenceName(), DistributedBus::class, 'convertAndPublishEvent', $distributedChannelName)
                    ->withParameterConverters(
                        [
                            GatewayPayloadBuilder::create('event'),
                            GatewayHeadersBuilder::create('metadata'),
                            GatewayHeaderBuilder::create('routingKey', DistributedBusHeader::DISTRIBUTED_ROUTING_KEY),
                            GatewayHeaderValueBuilder::create(DistributedBusHeader::DISTRIBUTED_SOURCE_SERVICE_NAME, $applicationConfiguration->serviceName),
                            GatewayHeaderValueBuilder::create(DistributedBusHeader::DISTRIBUTED_PAYLOAD_TYPE, 'event'),
                            GatewayHeaderValueBuilder::create(MessageHeaders::ROUTING_SLIP, DistributedBusHeader::DISTRIBUTED_ROUTING_SLIP_VALUE),
                        ]
                    )
                    ->withEndpointAnnotations($distributedServiceMap->distributedBusAnnotations)
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
                            GatewayHeaderValueBuilder::create(DistributedBusHeader::DISTRIBUTED_SOURCE_SERVICE_NAME, $applicationConfiguration->serviceName),
                            GatewayHeaderValueBuilder::create(DistributedBusHeader::DISTRIBUTED_PAYLOAD_TYPE, 'message'),
                            GatewayHeaderValueBuilder::create(MessageHeaders::ROUTING_SLIP, DistributedBusHeader::DISTRIBUTED_ROUTING_SLIP_VALUE),
                        ]
                    )
                    ->withEndpointAnnotations($distributedServiceMap->distributedBusAnnotations)
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
