<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Ecotone\Projecting\Config;

use function array_merge;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\EventSourcing\Attribute\ProjectionDelete;
use Ecotone\EventSourcing\Attribute\ProjectionInitialization;
use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Messaging\Attribute\ModuleAnnotation;
use Ecotone\Messaging\Config\Annotation\AnnotatedDefinitionReference;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\InterfaceToCallReference;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Endpoint\InboundChannelAdapter\InboundChannelAdapterBuilder;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvokerBuilder;
use Ecotone\Messaging\Handler\ServiceActivator\MessageProcessorActivatorBuilder;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\NamedEvent;
use Ecotone\Projecting\Attribute\Partitioned;
use Ecotone\Projecting\Attribute\Polling;
use Ecotone\Projecting\Attribute\ProjectionBackfill;
use Ecotone\Projecting\Attribute\ProjectionDeployment;
use Ecotone\Projecting\Attribute\ProjectionExecution;
use Ecotone\Projecting\Attribute\ProjectionFlush;
use Ecotone\Projecting\Attribute\ProjectionV2;
use Ecotone\Projecting\Attribute\Streaming;
use Ecotone\Projecting\EventStoreAdapter\PollingProjectionChannelAdapter;
use Ecotone\Projecting\EventStoreAdapter\StreamingProjectionMessageHandler;
use LogicException;

/**
 * This module register projection based on attributes
 */
#[ModuleAnnotation]
class ProjectingAttributeModule implements AnnotationModule
{
    /**
     * @param EcotoneProjectionExecutorBuilder[] $projectionBuilders
     * @param MessageProcessorActivatorBuilder[] $lifecycleHandlers
     * @param array<string, string> $pollingProjections Map of projection name to endpoint ID
     * @param array<string, array{streamingChannelName: string, endpointId: string, projectionBuilder: EcotoneProjectionExecutorBuilder}> $eventStreamingProjections
     */
    public function __construct(
        private array $projectionBuilders = [],
        private array $lifecycleHandlers = [],
        private array $pollingProjections = [],
        private array $eventStreamingProjections = []
    ) {
    }

    public static function create(AnnotationFinder $annotationRegistrationService, InterfaceToCallRegistry $interfaceToCallRegistry): static
    {
        $namedEvents = [];
        foreach ($annotationRegistrationService->findAnnotatedClasses(NamedEvent::class) as $className) {
            $attribute = $annotationRegistrationService->getAttributeForClass($className, NamedEvent::class);
            $namedEvents[$className] = $attribute->getName();
        }

        /** @var array<string, EcotoneProjectionExecutorBuilder> $projectionBuilders */
        $projectionBuilders = [];
        $pollingProjections = [];
        $eventStreamingProjections = [];
        foreach ($annotationRegistrationService->findAnnotatedClasses(ProjectionV2::class) as $projectionClassName) {
            $projectionAttribute = $annotationRegistrationService->getAttributeForClass($projectionClassName, ProjectionV2::class);
            $batchSizeAttribute = $annotationRegistrationService->findAttributeForClass($projectionClassName, ProjectionExecution::class);
            $backfillAttribute = $annotationRegistrationService->findAttributeForClass($projectionClassName, ProjectionBackfill::class);
            $pollingAttribute = $annotationRegistrationService->findAttributeForClass($projectionClassName, Polling::class);
            $streamingAttribute = $annotationRegistrationService->findAttributeForClass($projectionClassName, Streaming::class);
            $projectionDeployment = $annotationRegistrationService->findAttributeForClass($projectionClassName, ProjectionDeployment::class);
            $partitionAttribute = $annotationRegistrationService->findAttributeForClass($projectionClassName, Partitioned::class);

            $partitionHeaderName = $partitionAttribute?->partitionHeaderName;
            // Resolve automatic initialization: manualKickOff: true means automaticInitialization: false
            $automaticInitialization = self::resolveAutomaticInitialization($partitionAttribute, $projectionDeployment);
            $isLive = $projectionDeployment?->live ?? true;

            $projectionBuilder = new EcotoneProjectionExecutorBuilder(
                $projectionAttribute->name,
                $partitionHeaderName,
                $automaticInitialization,
                $isLive,
                $namedEvents,
                eventLoadingBatchSize: $batchSizeAttribute?->eventLoadingBatchSize,
                backfillPartitionBatchSize: $backfillAttribute?->backfillPartitionBatchSize,
                backfillAsyncChannelName: $backfillAttribute?->asyncChannelName,
            );

            $asynchronousChannelName = self::getProjectionAsynchronousChannel($annotationRegistrationService, $projectionClassName);
            $isPolling = $pollingAttribute !== null;
            $isEventStreaming = $streamingAttribute !== null;

            self::verifyCorrectApiUsage($isPolling, $asynchronousChannelName, $projectionAttribute, $isEventStreaming, $partitionHeaderName);

            if ($asynchronousChannelName !== null) {
                $projectionBuilder->setAsyncChannel($asynchronousChannelName);
            }

            if ($isPolling) {
                $pollingProjections[$projectionAttribute->name] = $pollingAttribute->endpointId;
            }

            if ($isEventStreaming) {
                $eventStreamingProjections[$projectionAttribute->name] = [
                    'streamingChannelName' => $streamingAttribute->channelName,
                    'endpointId' => $projectionAttribute->name,
                    'projectionBuilder' => $projectionBuilder,
                ];
            }

            $projectionBuilders[$projectionAttribute->name] = $projectionBuilder;
        }

        /** @var array<string, EcotoneProjectionExecutorBuilder> $projectionBuilders */
        $lifecycleHandlers = [];
        foreach ($annotationRegistrationService->findCombined(ProjectionV2::class, EventHandler::class) as $projectionEventHandler) {
            /** @var ProjectionV2 $projectionAttribute */
            $projectionAttribute = $projectionEventHandler->getAnnotationForClass();
            $projectionBuilder = $projectionBuilders[$projectionAttribute->name] ?? throw new LogicException();
            $projectionBuilder->addEventHandler($projectionEventHandler);
        }

        $lifecycleAnnotations = array_merge(
            $annotationRegistrationService->findCombined(ProjectionV2::class, ProjectionInitialization::class),
            $annotationRegistrationService->findCombined(ProjectionV2::class, ProjectionDelete::class),
            $annotationRegistrationService->findCombined(ProjectionV2::class, ProjectionFlush::class),
        );
        foreach ($lifecycleAnnotations as $lifecycleAnnotation) {
            /** @var ProjectionV2 $projectionAttribute */
            $projectionAttribute = $lifecycleAnnotation->getAnnotationForClass();
            $projectionBuilder = $projectionBuilders[$projectionAttribute->name] ?? throw new LogicException();
            $projectionReferenceName = AnnotatedDefinitionReference::getReferenceForClassName($annotationRegistrationService, $lifecycleAnnotation->getClassName());
            $inputChannel = 'projecting_lifecycle_handler:' . $projectionAttribute->name . ':' . $lifecycleAnnotation->getMethodName();
            if ($lifecycleAnnotation->getAnnotationForMethod() instanceof ProjectionInitialization) {
                $projectionBuilder->setInitChannel($inputChannel);
            } elseif ($lifecycleAnnotation->getAnnotationForMethod() instanceof ProjectionDelete) {
                $projectionBuilder->setDeleteChannel($inputChannel);
            } elseif ($lifecycleAnnotation->getAnnotationForMethod() instanceof ProjectionFlush) {
                $projectionBuilder->setFlushChannel($inputChannel);
            }


            $lifecycleHandlers[] = MessageProcessorActivatorBuilder::create()
                ->chainInterceptedProcessor(
                    MethodInvokerBuilder::create(
                        new Reference($projectionReferenceName),
                        InterfaceToCallReference::create($lifecycleAnnotation->getClassName(), $lifecycleAnnotation->getMethodName())
                    )
                )
                ->withInputChannelName($inputChannel);
        }

        return new self(array_values($projectionBuilders), $lifecycleHandlers, $pollingProjections, $eventStreamingProjections);
    }

    public function prepare(Configuration $messagingConfiguration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        foreach ($this->lifecycleHandlers as $lifecycleHandler) {
            $messagingConfiguration->registerMessageHandler($lifecycleHandler);
        }

        foreach ($this->pollingProjections as $projectionName => $endpointId) {
            $messagingConfiguration->registerConsumer(
                InboundChannelAdapterBuilder::createWithDirectObject(
                    ProjectingModule::inputChannelForProjectingManager($projectionName),
                    new PollingProjectionChannelAdapter(),
                    $interfaceToCallRegistry->getFor(PollingProjectionChannelAdapter::class, 'execute')
                )
                    ->withEndpointId($endpointId)
            );
        }

        foreach ($this->eventStreamingProjections as $projectionName => $config) {
            $projectorExecutorReference = ProjectingModule::getProjectorExecutorReference($projectionName);
            $projectionBuilder = $config['projectionBuilder'];
            $moduleReferenceSearchService->store(
                $projectorExecutorReference,
                $projectionBuilder
            );

            $handlerReference = StreamingProjectionMessageHandler::class . ':' . $projectionName;

            // Register the handler service
            $messagingConfiguration->registerServiceDefinition(
                $handlerReference,
                new Definition(StreamingProjectionMessageHandler::class, [
                    new Reference($projectorExecutorReference),
                    $projectionName,
                ])
            );

            $messagingConfiguration->registerMessageHandler(
                ServiceActivatorBuilder::create(
                    $handlerReference,
                    InterfaceToCallReference::create(StreamingProjectionMessageHandler::class, 'handle')
                )
                    ->withEndpointId($config['endpointId'])
                    ->withInputChannelName($config['streamingChannelName'])
            );
        }
    }

    public function canHandle($extensionObject): bool
    {
        return false;
    }

    public function getModuleExtensions(ServiceConfiguration $serviceConfiguration, array $serviceExtensions): array
    {
        // Filter out event streaming projections - they don't need ProjectingManager
        $eventStreamingProjectionNames = array_keys($this->eventStreamingProjections);
        return array_filter(
            $this->projectionBuilders,
            fn ($builder) => ! in_array($builder->projectionName(), $eventStreamingProjectionNames, true)
        );
    }

    public function getModulePackageName(): string
    {
        return ModulePackageList::CORE_PACKAGE;
    }

    /**
     * @param class-string $projectionClassName
     */
    private static function getProjectionAsynchronousChannel(AnnotationFinder $annotationRegistrationService, string $projectionClassName): ?string
    {
        $attributes = $annotationRegistrationService->getAnnotationsForClass($projectionClassName);
        foreach ($attributes as $attribute) {
            if ($attribute instanceof Asynchronous) {
                $asynchronousChannelName = $attribute->getChannelName();
                Assert::isTrue(count($asynchronousChannelName) === 1, "Make use of single channel name in Asynchronous annotation for Projection: {$projectionClassName}");
                return array_pop($asynchronousChannelName);
            }
        }
        return null;
    }

    private static function verifyCorrectApiUsage(bool $isPolling, ?string $asynchronousChannelName, ProjectionV2 $projectionAttribute, bool $isEventStreaming, null|string $partitionHeaderName): void
    {
        if ($isPolling && $asynchronousChannelName !== null) {
            throw ConfigurationException::create(
                "Projection '{$projectionAttribute->name}' cannot use both #[Polling] and #[Asynchronous] attributes. " .
                'A projection must be either polling-based or event-driven (synchronous/asynchronous), not both.'
            );
        }

        if ($isEventStreaming && $asynchronousChannelName !== null) {
            throw ConfigurationException::create(
                "Projection '{$projectionAttribute->name}' cannot use both #[StreamingProjection] and #[Asynchronous] attributes. " .
                'Event streaming projections consume directly from streaming channels.'
            );
        }

        if ($isPolling && $isEventStreaming) {
            throw ConfigurationException::create(
                "Projection '{$projectionAttribute->name}' cannot use both #[Polling] and #[Streaming] attributes. " .
                'A projection must be either polling-based or streaming-based, not both.'
            );
        }

        $isPartitioned = $partitionHeaderName !== null;
        if ($isPolling && $isPartitioned) {
            throw ConfigurationException::create(
                "Projection '{$projectionAttribute->name}' cannot use both #[Polling] and #[Partitioned] attributes. " .
                'Polling projections cannot be partitioned.'
            );
        }

        if ($isPartitioned && $isEventStreaming) {
            throw ConfigurationException::create(
                "Projection '{$projectionAttribute->name}' cannot use both #[Partitioned] and #[Streaming] attributes. " .
                'Partitioned projections cannot use streaming.'
            );
        }
    }

    private static function resolveAutomaticInitialization(
        ?Partitioned $partitionAttribute,
        ?ProjectionDeployment $projectionDeployment,
    ): bool {
        // Partitioned projections always require automatic initialization
        if ($partitionAttribute !== null) {
            return true;
        }

        // ProjectionDeployment: manualKickOff: true means automaticInitialization: false
        if ($projectionDeployment !== null) {
            return ! $projectionDeployment->manualKickOff;
        }

        // Default: automatic initialization is enabled
        return true;
    }
}
