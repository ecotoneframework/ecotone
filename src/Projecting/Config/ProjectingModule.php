<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\Projecting\Config;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\ModuleAnnotation;
use Ecotone\Api\Attribute\WithoutDatabaseTransaction;
use Ecotone\Api\Attribute\WithoutMessageCollector;
use Ecotone\Api\ExtensionObject\ModulePackageList;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\Projecting\ProjectionFlush;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ExtensionObjectResolver;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\Container\AttributeDefinition;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\InterfaceToCallReference;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Endpoint\Interceptor\TerminationListener;
use Ecotone\Messaging\Gateway\MessagingEntrypointService;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\HeaderBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\ValueBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvokerBuilder;
use Ecotone\Messaging\Handler\ServiceActivator\MessageProcessorActivatorBuilder;
use Ecotone\Messaging\Support\LicensingException;
use Ecotone\Projecting\EnterpriseProjectionNameHeader;
use Ecotone\Projecting\InMemory\InMemoryProjectionRegistry;
use Ecotone\Projecting\OpenCoreProjectionNameHeader;
use Ecotone\Projecting\PartitionBatchExecutorHandler;
use Ecotone\Projecting\PartitionProviderRegistry;
use Ecotone\Projecting\ProjectingHeaders;
use Ecotone\Projecting\ProjectingManager;
use Ecotone\Projecting\ProjectionOperationEndpoint;
use Ecotone\Projecting\ProjectionOperations;
use Ecotone\Projecting\ProjectionRegistry;
use Ecotone\Projecting\ProjectionStateStorageRegistry;
use Ecotone\Projecting\ProjectionTriggerEndpoint;
use Ecotone\Projecting\SinglePartitionProvider;
use Ecotone\Projecting\StreamFilterRegistry;
use Ecotone\Projecting\StreamSourceRegistry;

/**
 * This module allows to configure projections in a standard way
 * It does not depend on any particular way of defining projections (attributes, configurations, etc.)
 * It allows to register ProjectionExecutorBuilder and ProjectionComponentBuilder implementations
 */
#[ModuleAnnotation]
final class ProjectingModule implements AnnotationModule
{
    private const PROJECTING_MANAGER_CHANNEL_PREFIX = 'projecting_manager_handler:';

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
            foreach ($projectionBuilders as $builder) {
                if (! $builder instanceof EcotoneProjectionExecutorBuilder || ! $builder->isOpenSourceEligible()) {
                    throw LicensingException::create('Projections with enterprise features (Partitioned, Streaming, Polling, ProjectionRebuild, ProjectionDeployment, async backfill) require Ecotone Enterprise licence. See https://docs.ecotone.tech/enterprise for details.');
                }
            }
        }

        $messagingConfiguration->registerServiceDefinition(
            SinglePartitionProvider::class,
            new Definition(SinglePartitionProvider::class)
        );
        $messagingConfiguration->registerServiceDefinition(
            OpenCoreProjectionNameHeader::class,
            new Definition(OpenCoreProjectionNameHeader::class)
        );
        $messagingConfiguration->registerServiceDefinition(
            EnterpriseProjectionNameHeader::class,
            new Definition(EnterpriseProjectionNameHeader::class)
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
                    new Reference(MessagingEntrypointService::class),
                    $projectionBuilder->eventLoadingBatchSize(),
                    $projectionBuilder->automaticInitialization(),
                    $projectionBuilder->backfillPartitionBatchSize(),
                    $projectionBuilder->backfillAsyncChannelName(),
                    $projectionBuilder->rebuildPartitionBatchSize(),
                    $projectionBuilder->rebuildAsyncChannelName(),
                    $projectionBuilder->isPartitioned(),
                ])
            );
            $projectionRegistryMap[$projectionName] = new Reference($projectingManagerReference);

            $handlerBuilder = MessageProcessorActivatorBuilder::create()
                ->chainInterceptedProcessor(
                    MethodInvokerBuilder::create(
                        $projectingManagerReference,
                        InterfaceToCallReference::create(ProjectingManager::class, 'execute'),
                        [
                            $projectionBuilder->partitionHeader()
                                ? HeaderBuilder::create('partitionKeyValue', $projectionBuilder->partitionHeader())
                                : ($projectionBuilder->isPartitioned()
                                    ? new PartitionHeaderBuilder('partitionKeyValue')
                                    : ValueBuilder::create('partitionKeyValue', null)),
                            HeaderBuilder::createOptional('manualInitialization', ProjectingHeaders::MANUAL_INITIALIZATION),
                        ],
                    )
                )
                ->withEndpointId(self::endpointIdForProjection($projectionName))
                ->withInputChannelName(self::inputChannelForProjectingManager($projectionName));

            $triggerEndpointAnnotations = [AttributeDefinition::fromObject(new ProjectionTriggerEndpoint($projectionName))];
            $asyncAttribute = $projectionBuilder instanceof EcotoneProjectionExecutorBuilder ? $projectionBuilder->getAsyncAttribute() : null;
            if ($asyncAttribute !== null) {
                $endpointAnnotations = $asyncAttribute->asynchronousExecution;
                if ($messagingConfiguration->isRunningForEnterpriseLicence()) {
                    $endpointAnnotations = array_merge($endpointAnnotations, [new WithoutDatabaseTransaction(), new WithoutMessageCollector()]);
                }
                $triggerEndpointAnnotations[] = AttributeDefinition::fromObject(new Asynchronous(
                    $asyncAttribute->getChannelName(),
                    $endpointAnnotations,
                ));
            }
            $handlerBuilder = $handlerBuilder->withEndpointAnnotations($triggerEndpointAnnotations);

            $messagingConfiguration->registerMessageHandler($handlerBuilder);

            $messagingConfiguration->registerMessageHandler(
                MessageProcessorActivatorBuilder::create()
                    ->chainInterceptedProcessor(
                        MethodInvokerBuilder::create(
                            $projectingManagerReference,
                            InterfaceToCallReference::create(ProjectingManager::class, 'executePartitionBatch'),
                            [
                                HeaderBuilder::createOptional('partitionKeyValue', ProjectingHeaders::PROJECTION_PARTITION_KEY),
                                HeaderBuilder::create('canInitialize', ProjectingHeaders::PROJECTION_CAN_INITIALIZE),
                                HeaderBuilder::createOptional('shouldReset', ProjectingHeaders::PROJECTION_SHOULD_RESET),
                            ],
                        )
                    )
                    ->withInputChannelName(ProjectingManager::batchChannelFor($projectionName))
                    ->withEndpointAnnotations([AttributeDefinition::fromObject(new ProjectionFlush())])
            );

            foreach ([
                ProjectionOperationEndpoint::INITIALIZE => ['init', []],
                ProjectionOperationEndpoint::TRIGGER => ['executeAll', [ValueBuilder::create('manualInitialization', true)]],
                ProjectionOperationEndpoint::RESET => ['executeAllWithReset', []],
                ProjectionOperationEndpoint::BACKFILL => ['prepareBackfill', []],
                ProjectionOperationEndpoint::REBUILD => ['prepareRebuild', []],
                ProjectionOperationEndpoint::DELETE => ['delete', []],
            ] as $operation => [$method, $parameterConverters]) {
                $messagingConfiguration->registerMessageHandler(
                    MessageProcessorActivatorBuilder::create()
                        ->chainInterceptedProcessor(
                            MethodInvokerBuilder::create(
                                $projectingManagerReference,
                                InterfaceToCallReference::create(ProjectingManager::class, $method),
                                $parameterConverters,
                            )
                        )
                        ->withInputChannelName(ProjectionOperationEndpoint::channelFor($projectionName, $operation))
                        ->withEndpointAnnotations([AttributeDefinition::fromObject(new ProjectionOperationEndpoint($projectionName, $operation))])
                );
            }

            if ($projectionBuilder->asyncChannelName() !== null) {
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

        // Register PartitionBatchExecutorHandler and its message handler
        $messagingConfiguration->registerServiceDefinition(
            PartitionBatchExecutorHandler::class,
            new Definition(PartitionBatchExecutorHandler::class, [
                new Reference(ProjectionRegistry::class),
                new Reference(TerminationListener::class),
            ])
        );

        $messagingConfiguration->registerMessageHandler(
            MessageProcessorActivatorBuilder::create()
                ->chainInterceptedProcessor(
                    MethodInvokerBuilder::create(
                        PartitionBatchExecutorHandler::class,
                        InterfaceToCallReference::create(PartitionBatchExecutorHandler::class, 'executeBatch'),
                        [
                            PayloadBuilder::create('projectionName'),
                            HeaderBuilder::createOptional('limit', 'partitionBatch.limit'),
                            HeaderBuilder::createOptional('offset', 'partitionBatch.offset'),
                            HeaderBuilder::createOptional('streamName', 'partitionBatch.streamName'),
                            HeaderBuilder::createOptional('aggregateType', 'partitionBatch.aggregateType'),
                            HeaderBuilder::createOptional('eventStoreReferenceName', 'partitionBatch.eventStoreReferenceName'),
                            HeaderBuilder::createOptional('shouldReset', 'partitionBatch.shouldReset'),
                        ],
                    )
                )
                ->withEndpointId('partition_batch_executor_handler')
                ->withInputChannelName(PartitionBatchExecutorHandler::PARTITION_BATCH_EXECUTOR_CHANNEL)
        );

        // Register console commands
        $messagingConfiguration->registerServiceDefinition(
            ProjectionOperations::class,
            new Definition(ProjectionOperations::class, [new Reference(ProjectionRegistry::class), new Reference(MessagingEntrypointService::class)])
        );
        $messagingConfiguration->registerServiceDefinition(ProjectingConsoleCommands::class, new Definition(ProjectingConsoleCommands::class, [new Reference(ProjectionOperations::class)]));
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
        return self::PROJECTING_MANAGER_CHANNEL_PREFIX . $projectionName;
    }

    public static function projectionNameOfTriggerChannel(string $channelName): ?string
    {
        if (! str_starts_with($channelName, self::PROJECTING_MANAGER_CHANNEL_PREFIX)) {
            return null;
        }

        return substr($channelName, strlen(self::PROJECTING_MANAGER_CHANNEL_PREFIX));
    }
}
