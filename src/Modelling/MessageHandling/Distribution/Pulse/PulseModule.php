<?php

declare(strict_types=1);

namespace Ecotone\Modelling\MessageHandling\Distribution\Pulse;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\ModuleAnnotation;
use Ecotone\Api\ExtensionObject\DistributedServiceMap;
use Ecotone\Api\ExtensionObject\MessageChannelBuilder;
use Ecotone\Api\ExtensionObject\ModulePackageList;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\Gateway\DistributedBusHeader;
use Ecotone\Api\Gateway\EcotoneClockInterface;
use Ecotone\Api\Messaging\ConfiguredMessagingSystem;
use Ecotone\Api\Pulse\PulseConfiguration;
use Ecotone\Messaging\Channel\DepthProbedMessageChannelBuilder;
use Ecotone\Messaging\Channel\MessageChannelBuilderOnSameConnection;
use Ecotone\Messaging\Config\Annotation\AnnotatedDefinitionReference;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ExtensionObjectResolver;
use Ecotone\Messaging\Config\BootstrapConfiguration;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\Container\AttributeDefinition;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Endpoint\PollingConsumer\PolledMessageFailureAnswer;
use Ecotone\Messaging\Gateway\MessagingEntrypointService;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Logger\LoggingGateway;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\AllHeadersBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\HeaderBuilder;
use Ecotone\Messaging\Handler\ServiceActivator\MessageProcessorActivatorBuilder;
use Ecotone\Messaging\NullableMessageChannel;
use Ecotone\Messaging\Support\LicensingException;

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
 *
 * @internal
 */
final class PulseModule implements AnnotationModule
{
    private const IMPLIED_SERVICE_MAP_REFERENCE = 'ecotone.pulse.distributed_bus';

    /**
     * @param string[] $asynchronousHandlerChannels
     */
    private function __construct(private array $asynchronousHandlerChannels)
    {
    }

    public static function create(AnnotationFinder $annotationRegistrationService, InterfaceToCallRegistry $interfaceToCallRegistry): static
    {
        $asynchronousHandlerChannels = [];
        foreach ($annotationRegistrationService->findAnnotatedMethods(Asynchronous::class) as $registration) {
            $asynchronousHandlerChannels = [...$asynchronousHandlerChannels, ...$registration->getAnnotationForMethod()->getChannelName()];
        }
        foreach ($annotationRegistrationService->findAnnotatedClasses(Asynchronous::class) as $className) {
            $asynchronousHandlerChannels = [...$asynchronousHandlerChannels, ...AnnotatedDefinitionReference::getSingleAnnotationForClass($annotationRegistrationService, $className, Asynchronous::class)->getChannelName()];
        }

        return new self(array_values(array_unique($asynchronousHandlerChannels)));
    }

    public function prepare(Configuration $messagingConfiguration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        $pulseConfigurations = ExtensionObjectResolver::resolve(PulseConfiguration::class, $extensionObjects);
        if ($pulseConfigurations === []) {
            return;
        }

        if (count($pulseConfigurations) > 1) {
            throw ConfigurationException::create('PulseConfiguration was registered ' . count($pulseConfigurations) . ' times, but a service answers to exactly one Pulse service. Keep a single #[ServiceContext] returning PulseConfiguration::createWithDefaults(pulseServiceName: ...) and remove the others.');
        }

        $pulseServiceName = $pulseConfigurations[0]->pulseServiceName;
        if (! $messagingConfiguration->isRunningForEnterpriseLicence()) {
            throw LicensingException::create('Ecotone Pulse is available only as part of Ecotone Enterprise. Provide a licence key, or remove the PulseConfiguration from your #[ServiceContext]. See https://docs.ecotone.tech/enterprise for details.');
        }

        $serviceConfiguration = ExtensionObjectResolver::resolveUnique(ServiceConfiguration::class, $extensionObjects, ServiceConfiguration::createWithDefaults());
        if ($serviceConfiguration->serviceName === ServiceConfiguration::DEFAULT_SERVICE_NAME) {
            throw ConfigurationException::create("PulseConfiguration needs this service to be named, so Pulse can tell which service answered. Set it with ServiceConfiguration::createWithDefaults()->withServiceName('my_service') in a #[ServiceContext].");
        }

        $serviceMaps = ExtensionObjectResolver::resolve(DistributedServiceMap::class, $extensionObjects);
        self::guardReplyChannel($pulseConfigurations[0], $serviceConfiguration->serviceName, $serviceMaps, $extensionObjects);

        $bootstrapConfiguration = ExtensionObjectResolver::resolveUnique(BootstrapConfiguration::class, $extensionObjects, BootstrapConfiguration::createWithDefaults());
        $tenancy = ExtensionObjectResolver::resolve(PulseTenancyRegistration::class, $extensionObjects)[0] ?? null;
        ['channels' => $channels, 'probes' => $probes] = $this->channelsOf($serviceMaps, $extensionObjects, $tenancy === null ? [] : $tenancy->tenantProbeGroups);
        $sectionRegistrations = ExtensionObjectResolver::resolve(PulseSnapshotSectionRegistration::class, $extensionObjects);

        $messagingConfiguration->registerServiceDefinition(
            PulseSnapshot::class,
            Definition::createFor(PulseSnapshot::class, [
                $serviceConfiguration->serviceName,
                $pulseServiceName,
                PulseFrameworkResolver::resolve($bootstrapConfiguration),
                array_map(self::topologyOf(...), $serviceMaps),
                $channels,
                $probes,
                self::sectionReferences($sectionRegistrations, tenantScoped: false),
                self::sectionReferences($sectionRegistrations, tenantScoped: true),
                $tenancy === null ? [] : ['header' => $tenancy->tenantHeaderName, 'tenants' => $tenancy->tenants],
                new Reference(ConfiguredMessagingSystem::class),
                new Reference(EcotoneClockInterface::class),
                new Reference(ExtensionObjectResolver::resolve(PulseSectionIsolationRegistration::class, $extensionObjects)[0]->serviceReference ?? NoPulseSectionIsolation::class),
            ])
        );
        $messagingConfiguration->registerServiceDefinition(
            PolledMessageFailureAnswer::class,
            Definition::createFor(PulseRequestFailureAnswer::class, [
                new Reference(PulseSnapshot::class),
                new Reference(MessagingEntrypointService::class),
                new Reference(LoggingGateway::class),
            ])
        );
        $messagingConfiguration->registerServiceDefinition(NoPulseSectionIsolation::class, Definition::createFor(NoPulseSectionIsolation::class, []));
        $messagingConfiguration->registerMessageHandler(
            MessageProcessorActivatorBuilder::createForMethod(PulseSnapshot::class, $interfaceToCallRegistry->getFor(PulseSnapshot::class, 'snapshotJson'))
                ->withInputChannelName(PulseSnapshot::ROUTING_KEY)
                ->withMethodParameterConverters([
                    HeaderBuilder::createOptional('sourceService', DistributedBusHeader::DISTRIBUTED_SOURCE_SERVICE_NAME),
                    AllHeadersBuilder::createWith('headers'),
                ])
        );
    }

    /**
     * @param DistributedServiceMap[] $serviceMaps
     * @return array{channels: list<array{name: string, kind: string, probeGroup: string|null, destination: string|null, tenantScoped: bool}>, probes: array<string, Definition>}
     */
    private function channelsOf(array $serviceMaps, array $extensionObjects, array $tenantProbeGroups): array
    {
        $outboundChannels = [];
        foreach ($serviceMaps as $serviceMap) {
            $topology = self::topologyOf($serviceMap);
            $outboundChannels = [
                ...$outboundChannels,
                ...array_values($topology['commandMapping']),
                ...array_column($topology['eventSubscriptions'], 'channel'),
                ...($topology['asynchronousChannel'] === null ? [] : [$topology['asynchronousChannel']]),
            ];
        }

        $channels = [];
        $probes = [];
        foreach (ExtensionObjectResolver::resolve(MessageChannelBuilder::class, $extensionObjects) as $channelBuilder) {
            $name = $channelBuilder->getMessageChannelName();
            if ($channelBuilder->isPollable() && $name !== NullableMessageChannel::CHANNEL_NAME && ! in_array($name, $outboundChannels, true)) {
                $probeGroup = $channelBuilder instanceof DepthProbedMessageChannelBuilder ? $channelBuilder->depthProbeGroup() : null;
                if ($probeGroup !== null && ! isset($probes[$probeGroup])) {
                    $probes[$probeGroup] = $channelBuilder->depthProbeDefinition();
                }
                $channels[$name] = ['name' => $name, 'kind' => in_array($name, $this->asynchronousHandlerChannels, true) ? 'async' : 'queue', 'probeGroup' => $probeGroup, 'destination' => $probeGroup === null ? null : $channelBuilder->depthProbeDestination(), 'tenantScoped' => $probeGroup !== null && in_array($probeGroup, $tenantProbeGroups, true)];
            }
        }
        ksort($channels);

        return ['channels' => array_values($channels), 'probes' => $probes];
    }

    public function getModuleExtensions(ServiceConfiguration $serviceConfiguration, array $serviceExtensions): array
    {
        $pulseConfigurations = ExtensionObjectResolver::resolve(PulseConfiguration::class, $serviceExtensions);
        $bootstrapConfiguration = ExtensionObjectResolver::resolveUnique(BootstrapConfiguration::class, $serviceExtensions, BootstrapConfiguration::createWithDefaults());
        $serviceName = ExtensionObjectResolver::resolveUnique(ServiceConfiguration::class, $serviceExtensions, $serviceConfiguration)->serviceName;
        if (count($pulseConfigurations) !== 1 || $bootstrapConfiguration->licenceKey === null || $serviceName === ServiceConfiguration::DEFAULT_SERVICE_NAME) {
            return [];
        }

        $serviceMaps = ExtensionObjectResolver::resolve(DistributedServiceMap::class, $serviceExtensions);
        $impliedServiceMap = self::impliedServiceMap($pulseConfigurations[0], $serviceMaps);
        if ($impliedServiceMap === null) {
            return [];
        }

        $replyChannel = $pulseConfigurations[0]->replyChannel ?? $pulseConfigurations[0]->pulseServiceName;
        if (self::channelBuilderNamed($replyChannel, $serviceExtensions) !== null) {
            return [$impliedServiceMap];
        }

        $requestBuilder = self::channelBuilderNamed($pulseConfigurations[0]->requestChannel ?? $serviceName, $serviceExtensions);
        if (! $requestBuilder instanceof MessageChannelBuilderOnSameConnection) {
            return [];
        }

        return [$requestBuilder->forSendOnlyChannelOnSameConnection($replyChannel), $impliedServiceMap];
    }

    public function getModulePackageName(): string
    {
        return ModulePackageList::CORE_PACKAGE;
    }

    /**
     * @param PulseSnapshotSectionRegistration[] $registrations
     * @return array<string, Reference>
     */
    private static function sectionReferences(array $registrations, bool $tenantScoped): array
    {
        $references = [];
        foreach ($registrations as $registration) {
            if (in_array($registration->sectionName, PulseSnapshot::CORE_KEYS, true)) {
                throw ConfigurationException::create("Pulse snapshot section `{$registration->sectionName}` collides with a key the snapshot already has. Name the section anything but " . implode(', ', PulseSnapshot::CORE_KEYS) . '.');
            }
            if ($registration->tenantScoped !== $tenantScoped) {
                continue;
            }
            if (array_key_exists($registration->sectionName, $references)) {
                throw ConfigurationException::create("Pulse snapshot section `{$registration->sectionName}` was registered twice, so the snapshot could hold only one of them. Register each section under its own name, or remove the duplicate registration.");
            }
            $references[$registration->sectionName] = new Reference($registration->serviceReference);
        }

        return $references;
    }

    /**
     * @param DistributedServiceMap[] $serviceMaps
     * @param object[] $extensionObjects
     */
    private static function guardReplyChannel(PulseConfiguration $configuration, string $serviceName, array $serviceMaps, array $extensionObjects): void
    {
        $mappedChannel = self::mappedChannelOf($serviceMaps, $configuration->pulseServiceName);
        if ($mappedChannel !== null && $configuration->replyChannel !== null && $mappedChannel !== $configuration->replyChannel) {
            throw ConfigurationException::create("PulseConfiguration sends the reply to channel '{$configuration->replyChannel}' (withReplyChannel), but a DistributedServiceMap maps Pulse service {$configuration->pulseServiceName} to channel '{$mappedChannel}', so the reply could go to only one of them. Make both name the same channel: change withReplyChannel('{$configuration->replyChannel}') to withReplyChannel('{$mappedChannel}'), or remove withReplyChannel to follow the DistributedServiceMap, or remove the command mapping of '{$configuration->pulseServiceName}' from the DistributedServiceMap.");
        }

        $replyChannel = $mappedChannel ?? $configuration->replyChannel ?? $configuration->pulseServiceName;
        $requestChannel = $configuration->requestChannel ?? $serviceName;
        if ($replyChannel === $requestChannel) {
            throw ConfigurationException::create("Pulse replies would go to channel '{$replyChannel}', which is also the channel Pulse sends its requests to, so the service would consume its own replies. Choose another reply channel with PulseConfiguration::withReplyChannel(), or another request channel with PulseConfiguration::withRequestChannel().");
        }

        if ($mappedChannel !== null) {
            return;
        }

        if (self::channelBuilderNamed($replyChannel, $extensionObjects) !== null) {
            return;
        }

        $registerExplicitly = "register '{$replyChannel}' explicitly, e.g. AmqpBackedMessageChannelBuilder::create('{$replyChannel}') (or the DbalBackedMessageChannelBuilder, RedisBackedMessageChannelBuilder, SqsBackedMessageChannelBuilder or KafkaMessageChannelBuilder::create('{$replyChannel}') your other channels use)";
        if (self::channelBuilderNamed($requestChannel, $extensionObjects) === null) {
            throw ConfigurationException::create("Pulse replies go to channel '{$replyChannel}', which is not a registered message channel, and it cannot follow the request channel's transport because the request channel '{$requestChannel}' is not registered either. Register the channel Pulse sends its requests to, named after the service ('{$serviceName}') or chosen with PulseConfiguration::withRequestChannel('...'). Or {$registerExplicitly}, or send the replies to another registered channel with PulseConfiguration::withReplyChannel().");
        }

        throw ConfigurationException::create("Pulse replies go to channel '{$replyChannel}', which is not a registered message channel, and the builder of request channel '{$requestChannel}' cannot be re-created for another channel name. " . ucfirst($registerExplicitly) . ', or send the replies to another registered channel with PulseConfiguration::withReplyChannel().');
    }

    /**
     * @param object[] $extensionObjects
     */
    private static function channelBuilderNamed(string $channelName, array $extensionObjects): ?MessageChannelBuilder
    {
        foreach (ExtensionObjectResolver::resolve(MessageChannelBuilder::class, $extensionObjects) as $channelBuilder) {
            if ($channelBuilder->getMessageChannelName() === $channelName) {
                return $channelBuilder;
            }
        }

        return null;
    }

    /**
     * @param DistributedServiceMap[] $serviceMaps
     */
    private static function impliedServiceMap(PulseConfiguration $configuration, array $serviceMaps): ?DistributedServiceMap
    {
        if (self::mappedChannelOf($serviceMaps, $configuration->pulseServiceName) !== null) {
            return null;
        }

        return DistributedServiceMap::initialize(self::IMPLIED_SERVICE_MAP_REFERENCE)
            ->withCommandMapping($configuration->pulseServiceName, $configuration->replyChannel ?? $configuration->pulseServiceName);
    }

    /**
     * @param DistributedServiceMap[] $serviceMaps
     */
    private static function mappedChannelOf(array $serviceMaps, string $serviceName): ?string
    {
        foreach ($serviceMaps as $serviceMap) {
            if (array_key_exists($serviceName, $serviceMap->commandMapping)) {
                return $serviceMap->commandMapping[$serviceName];
            }
        }

        return null;
    }

    /**
     * @return array{referenceName: string, commandMapping: array<string, string>, eventSubscriptions: array<mixed>, asynchronousChannel: string|null}
     */
    private static function topologyOf(DistributedServiceMap $serviceMap): array
    {
        $asynchronousChannel = null;
        foreach ($serviceMap->distributedBusAnnotations as $annotation) {
            if ($annotation instanceof AttributeDefinition && $annotation->getClassName() === Asynchronous::class) {
                $asynchronousChannel = $annotation->getArguments()[0];
            }
        }

        $subscriptions = [];
        foreach ($serviceMap->eventSubscriptions as $channel => $subscription) {
            $subscriptions[] = [
                'channel' => $channel,
                'subscriptionKeys' => $subscription['keys'],
                'excludePublishingServices' => $subscription['exclude'],
                'includePublishingServices' => $subscription['include'],
            ];
        }

        return [
            'referenceName' => $serviceMap->getReferenceName(),
            'commandMapping' => $serviceMap->commandMapping,
            'eventSubscriptions' => $subscriptions,
            'asynchronousChannel' => $asynchronousChannel,
        ];
    }
}
