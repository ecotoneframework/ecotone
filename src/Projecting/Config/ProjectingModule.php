<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Ecotone\Projecting\Config;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Attribute\ModuleAnnotation;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ExtensionObjectResolver;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\InterfaceToCallReference;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Endpoint\Interceptor\TerminationListener;
use Ecotone\Messaging\Gateway\MessagingEntrypoint;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\HeaderBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\ValueBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvokerBuilder;
use Ecotone\Messaging\Handler\ServiceActivator\MessageProcessorActivatorBuilder;
use Ecotone\Projecting\BackfillExecutorHandler;
use Ecotone\Projecting\InMemory\InMemoryProjectionRegistry;
use Ecotone\Projecting\PartitionProviderRegistry;
use Ecotone\Projecting\ProjectingHeaders;
use Ecotone\Projecting\ProjectingManager;
use Ecotone\Projecting\ProjectionRegistry;
use Ecotone\Projecting\ProjectionStateStorageRegistry;
use Ecotone\Projecting\SinglePartitionProvider;
use Ecotone\Projecting\StreamFilterRegistry;
use Ecotone\Projecting\StreamSourceRegistry;

/**
 * This module allows to configure projections in a standard way
 * It does not depend on any particular way of defining projections (attributes, configurations, etc.)
 * It allows to register ProjectionExecutorBuilder and ProjectionComponentBuilder implementations
 */
#[ModuleAnnotation]
class ProjectingModule implements AnnotationModule
{
    public static function getProjectorExecutorReference(string $projectionName): string
    {
        return 'projection_executor:' . $projectionName;
    }

    public static function create(AnnotationFinder $annotationRegistrationService, InterfaceToCallRegistry $interfaceToCallRegistry): static
    {
        return new self();
    }

    public function prepare(Configuration $messagingConfiguration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        $serviceConfiguration = ExtensionObjectResolver::resolveUnique(ServiceConfiguration::class, $extensionObjects, ServiceConfiguration::createWithDefaults());
        $projectionBuilders = ExtensionObjectResolver::resolve(ProjectionExecutorBuilder::class, $extensionObjects);

        if (! empty($projectionBuilders) && ! $messagingConfiguration->isRunningForEnterpriseLicence()) {
            throw ConfigurationException::create('Projections are part of Ecotone Enterprise. To use projections, please acquire an enterprise licence.');
        }

        $messagingConfiguration->registerServiceDefinition(
            SinglePartitionProvider::class,
            new Definition(SinglePartitionProvider::class)
        );

        $projectionRegistryMap = [];
        foreach ($projectionBuilders as $projectionBuilder) {
            $projectionName = $projectionBuilder->projectionName();
            $reference = self::getProjectorExecutorReference($projectionName);
            $moduleReferenceSearchService->store($reference, $projectionBuilder);

            $messagingConfiguration->registerServiceDefinition(
                $projectingManagerReference = ProjectingManager::class . ':' . $projectionName,
                new Definition(ProjectingManager::class, [
                    new Reference(ProjectionStateStorageRegistry::class),
                    new Reference($reference),
                    new Reference(StreamSourceRegistry::class),
                    new Reference(PartitionProviderRegistry::class),
                    new Reference(StreamFilterRegistry::class),
                    $projectionName,
                    new Reference(TerminationListener::class),
                    new Reference(MessagingEntrypoint::class),
                    $projectionBuilder->eventLoadingBatchSize(),
                    $projectionBuilder->automaticInitialization(),
                    $projectionBuilder->backfillPartitionBatchSize(),
                    $projectionBuilder->backfillAsyncChannelName(),
                ])
            );
            $projectionRegistryMap[$projectionName] = new Reference($projectingManagerReference);

            $messagingConfiguration->registerMessageHandler(
                MessageProcessorActivatorBuilder::create()
                    ->chainInterceptedProcessor(
                        MethodInvokerBuilder::create(
                            $projectingManagerReference,
                            InterfaceToCallReference::create(ProjectingManager::class, 'execute'),
                            [
                                $projectionBuilder->partitionHeader()
                                    ? HeaderBuilder::create('partitionKeyValue', $projectionBuilder->partitionHeader())
                                    : ValueBuilder::create('partitionKeyValue', null),
                                HeaderBuilder::createOptional('manualInitialization', ProjectingHeaders::MANUAL_INITIALIZATION),
                            ],
                        )
                    )
                    ->withEndpointId(self::endpointIdForProjection($projectionName))
                    ->withInputChannelName(self::inputChannelForProjectingManager($projectionName))
            );

            // Should the projection be triggered asynchronously?
            if (
                $serviceConfiguration->isModulePackageEnabled(ModulePackageList::ASYNCHRONOUS_PACKAGE)
                && $projectionBuilder->asyncChannelName() !== null
            ) {
                $messagingConfiguration->registerAsynchronousEndpoint(
                    $projectionBuilder->asyncChannelName(),
                    self::endpointIdForProjection($projectionName),
                );
            }
        }

        // Register ProjectionRegistry
        $messagingConfiguration->registerServiceDefinition(
            ProjectionRegistry::class,
            new Definition(InMemoryProjectionRegistry::class, [$projectionRegistryMap])
        );

        // Register BackfillExecutorHandler and its message handler
        $messagingConfiguration->registerServiceDefinition(
            BackfillExecutorHandler::class,
            new Definition(BackfillExecutorHandler::class, [
                new Reference(ProjectionRegistry::class),
                new Reference(TerminationListener::class),
            ])
        );

        $messagingConfiguration->registerMessageHandler(
            MessageProcessorActivatorBuilder::create()
                ->chainInterceptedProcessor(
                    MethodInvokerBuilder::create(
                        BackfillExecutorHandler::class,
                        InterfaceToCallReference::create(BackfillExecutorHandler::class, 'executeBackfillBatch'),
                        [
                            PayloadBuilder::create('projectionName'),
                            HeaderBuilder::createOptional('limit', 'backfill.limit'),
                            HeaderBuilder::createOptional('offset', 'backfill.offset'),
                            HeaderBuilder::createOptional('streamName', 'backfill.streamName'),
                            HeaderBuilder::createOptional('aggregateType', 'backfill.aggregateType'),
                            HeaderBuilder::createOptional('eventStoreReferenceName', 'backfill.eventStoreReferenceName'),
                        ],
                    )
                )
                ->withEndpointId('backfill_executor_handler')
                ->withInputChannelName(BackfillExecutorHandler::BACKFILL_EXECUTOR_CHANNEL)
        );

        // Register console commands
        $messagingConfiguration->registerServiceDefinition(ProjectingConsoleCommands::class, new Definition(ProjectingConsoleCommands::class, [new Reference(ProjectionRegistry::class)]));
    }

    public function canHandle($extensionObject): bool
    {
        return $extensionObject instanceof ServiceConfiguration
            || $extensionObject instanceof ProjectionExecutorBuilder;
    }

    public function getModuleExtensions(ServiceConfiguration $serviceConfiguration, array $serviceExtensions, ?InterfaceToCallRegistry $interfaceToCallRegistry = null): array
    {
        return [new ProjectingModuleRoutingExtension(self::inputChannelForProjectingManager(...))];
    }

    public function getModulePackageName(): string
    {
        return ModulePackageList::CORE_PACKAGE;
    }

    private static function endpointIdForProjection(string $projectionName): string
    {
        return 'projecting_manager_endpoint:' . $projectionName;
    }

    public static function inputChannelForProjectingManager(string $projectionName): string
    {
        return 'projecting_manager_handler:' . $projectionName;
    }
}
