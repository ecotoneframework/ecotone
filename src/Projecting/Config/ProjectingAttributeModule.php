<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\Projecting\Config;

use function array_merge;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\Header;
use Ecotone\Api\Attribute\ModuleAnnotation;
use Ecotone\Api\Attribute\NamedEvent;
use Ecotone\Api\Attribute\Polling;
use Ecotone\Api\ExtensionObject\ModulePackageList;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\Projecting\Partitioned;
use Ecotone\Api\Projecting\Projection;
use Ecotone\Api\Projecting\ProjectionBackfill;
use Ecotone\Api\Projecting\ProjectionDelete;
use Ecotone\Api\Projecting\ProjectionDeployment;
use Ecotone\Api\Projecting\ProjectionExecution;
use Ecotone\Api\Projecting\ProjectionFlush;
use Ecotone\Api\Projecting\ProjectionInitialization;
use Ecotone\Api\Projecting\ProjectionName;
use Ecotone\Api\Projecting\ProjectionRebuild;
use Ecotone\Api\Projecting\ProjectionReset;
use Ecotone\Api\Projecting\Streaming;
use Ecotone\Messaging\Config\Annotation\AnnotatedDefinitionReference;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\InterfaceToCallReference;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Endpoint\InboundChannelAdapter\InboundChannelAdapterBuilder;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvokerBuilder;
use Ecotone\Messaging\Handler\ServiceActivator\MessageProcessorActivatorBuilder;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\LicensingException;
use Ecotone\Projecting\EventStoreAdapter\PollingProjectionChannelAdapter;
use Ecotone\Projecting\EventStoreAdapter\StreamingProjectionMessageHandler;
use Ecotone\Projecting\ProjectingHeaders;
use LogicException;
use ReflectionAttribute;
use ReflectionMethod;

/**
 * This module register projection based on attributes
 */
#[ModuleAnnotation]
final class ProjectingAttributeModule implements AnnotationModule
{
    /**
     * @param EcotoneProjectionExecutorBuilder[] $projectionBuilders
     * @param MessageProcessorActivatorBuilder[] $lifecycleHandlers
     * @param array<string, string> $pollingProjections Map of projection name to endpoint ID
     * @param array<string, array{streamingChannelName: string, endpointId: string, projectionBuilder: EcotoneProjectionExecutorBuilder}> $eventStreamingProjections
     * @param array<int, array{handler: string, parameter: string, projectionName: string}> $handlersRequiringProjectionName
     */
    public function __construct(
        private array $projectionBuilders = [],
        private array $lifecycleHandlers = [],
        private array $pollingProjections = [],
        private array $eventStreamingProjections = [],
        private bool $hasFlushWithProjectionState = false,
        private array $handlersRequiringProjectionName = [],
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
        foreach ($annotationRegistrationService->findAnnotatedClasses(Projection::class) as $projectionClassName) {
            $projectionAttribute = $annotationRegistrationService->getAttributeForClass($projectionClassName, Projection::class);
            $batchSizeAttribute = $annotationRegistrationService->findAttributeForClass($projectionClassName, ProjectionExecution::class);
            $backfillAttribute = $annotationRegistrationService->findAttributeForClass($projectionClassName, ProjectionBackfill::class);
            $rebuildAttribute = $annotationRegistrationService->findAttributeForClass($projectionClassName, ProjectionRebuild::class);
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
                partitioned: $partitionAttribute !== null,
                rebuildPartitionBatchSize: $rebuildAttribute?->partitionBatchSize,
                rebuildAsyncChannelName: $rebuildAttribute?->asyncChannelName,
                hasRebuild: $rebuildAttribute !== null,
                hasDeployment: $projectionDeployment !== null,
            );

            $asyncAttribute = self::getProjectionAsynchronousAttribute($annotationRegistrationService, $projectionClassName);
            $asynchronousChannelName = $asyncAttribute ? $asyncAttribute->getChannelName()[0] : null;
            $isPolling = $pollingAttribute !== null;
            $isEventStreaming = $streamingAttribute !== null;

            self::verifyCorrectApiUsage($isPolling, $asynchronousChannelName, $projectionAttribute, $isEventStreaming, $partitionAttribute !== null, $projectionDeployment?->manualKickOff ?? false);

            if ($asyncAttribute !== null) {
                $projectionBuilder->setAsyncAttribute($asyncAttribute);
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
        $handlersRequiringProjectionName = [];
        foreach ($annotationRegistrationService->findCombined(Projection::class, EventHandler::class) as $projectionEventHandler) {
            /** @var Projection $projectionAttribute */
            $projectionAttribute = $projectionEventHandler->getAnnotationForClass();
            $projectionBuilder = $projectionBuilders[$projectionAttribute->name] ?? throw new LogicException();
            $projectionBuilder->addEventHandler($projectionEventHandler);
            $handlersRequiringProjectionName = array_merge(
                $handlersRequiringProjectionName,
                self::parametersRequiringProjectionName($interfaceToCallRegistry, $projectionEventHandler->getClassName(), $projectionEventHandler->getMethodName(), $projectionAttribute->name),
            );
        }

        $lifecycleAnnotations = array_merge(
            $annotationRegistrationService->findCombined(Projection::class, ProjectionInitialization::class),
            $annotationRegistrationService->findCombined(Projection::class, ProjectionDelete::class),
            $annotationRegistrationService->findCombined(Projection::class, ProjectionFlush::class),
            $annotationRegistrationService->findCombined(Projection::class, ProjectionReset::class),
        );
        $hasFlushWithProjectionState = false;
        foreach ($lifecycleAnnotations as $lifecycleAnnotation) {
            /** @var Projection $projectionAttribute */
            $projectionAttribute = $lifecycleAnnotation->getAnnotationForClass();
            $projectionBuilder = $projectionBuilders[$projectionAttribute->name] ?? throw new LogicException();
            $projectionReferenceName = AnnotatedDefinitionReference::getReferenceForClassName($annotationRegistrationService, $lifecycleAnnotation->getClassName());
            $inputChannel = 'projecting_lifecycle_handler:' . $projectionAttribute->name . ':' . $lifecycleAnnotation->getMethodName();
            $handlersRequiringProjectionName = array_merge(
                $handlersRequiringProjectionName,
                self::parametersRequiringProjectionName($interfaceToCallRegistry, $lifecycleAnnotation->getClassName(), $lifecycleAnnotation->getMethodName(), $projectionAttribute->name),
            );
            if ($lifecycleAnnotation->getAnnotationForMethod() instanceof ProjectionInitialization) {
                $projectionBuilder->setInitChannel($inputChannel);
            } elseif ($lifecycleAnnotation->getAnnotationForMethod() instanceof ProjectionDelete) {
                $projectionBuilder->setDeleteChannel($inputChannel);
            } elseif ($lifecycleAnnotation->getAnnotationForMethod() instanceof ProjectionFlush) {
                $projectionBuilder->setFlushChannel($inputChannel);
                if (self::flushMethodUsesProjectionState($lifecycleAnnotation->getClassName(), $lifecycleAnnotation->getMethodName())) {
                    $hasFlushWithProjectionState = true;
                }
            } elseif ($lifecycleAnnotation->getAnnotationForMethod() instanceof ProjectionReset) {
                $projectionBuilder->setResetChannel($inputChannel);
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

        return new self(
            array_values($projectionBuilders),
            $lifecycleHandlers,
            $pollingProjections,
            $eventStreamingProjections,
            $hasFlushWithProjectionState,
            handlersRequiringProjectionName: $handlersRequiringProjectionName,
        );
    }

    public function prepare(Configuration $messagingConfiguration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        if (! $messagingConfiguration->isRunningForEnterpriseLicence()) {
            if (! empty($this->pollingProjections)) {
                throw LicensingException::create('#[Polling] projections require Ecotone Enterprise licence. See https://docs.ecotone.tech/enterprise for details.');
            }
            if (! empty($this->eventStreamingProjections)) {
                throw LicensingException::create('#[Streaming] projections require Ecotone Enterprise licence. See https://docs.ecotone.tech/enterprise for details.');
            }
            if ($this->hasFlushWithProjectionState) {
                throw LicensingException::create('Using #[ProjectionState] in #[ProjectionFlush] methods requires Ecotone Enterprise licence. See https://docs.ecotone.tech/enterprise for details.');
            }
            foreach ($this->handlersRequiringProjectionName as $required) {
                throw LicensingException::create("#[ProjectionName] on \${$required['parameter']} of {$required['handler']} requires the projection name, which is passed to a projection handler only under an Ecotone Enterprise licence, and this application runs without one: a parameter that is neither nullable nor defaulted would fail on every call. Either declare it as ?string \${$required['parameter']} = null to receive null without a licence, remove #[ProjectionName] if the handler does not need the name (the projection declares it in #[Projection('{$required['projectionName']}')]), or obtain an Enterprise licence (see https://docs.ecotone.tech/enterprise) and pass it as the licenceKey of your framework's Ecotone configuration or of EcotoneLite::bootstrap().");
            }
        }

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
    private static function getProjectionAsynchronousAttribute(AnnotationFinder $annotationRegistrationService, string $projectionClassName): ?Asynchronous
    {
        $attributes = $annotationRegistrationService->getAnnotationsForClass($projectionClassName);
        foreach ($attributes as $attribute) {
            if ($attribute instanceof Asynchronous) {
                $asynchronousChannelName = $attribute->getChannelName();
                Assert::isTrue(count($asynchronousChannelName) === 1, "Make use of single channel name in Asynchronous annotation for Projection: {$projectionClassName}");
                return $attribute;
            }
        }
        return null;
    }

    private static function verifyCorrectApiUsage(bool $isPolling, ?string $asynchronousChannelName, Projection $projectionAttribute, bool $isEventStreaming, bool $isPartitioned, bool $isManualKickOff): void
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

        if ($isPartitioned && $isManualKickOff) {
            throw ConfigurationException::create(
                "Projection '{$projectionAttribute->name}' combines #[Partitioned] with #[ProjectionDeployment(manualKickOff: true)], but a partitioned projection initializes each partition on that partition's first event, so it cannot wait to be kicked off and manualKickOff would be ignored. " .
                "Either remove manualKickOff: true from #[ProjectionDeployment] and let each partition initialize on its first event, or remove #[Partitioned] to keep the projection waiting until it is kicked off with ecotone:projection:init {$projectionAttribute->name}."
            );
        }
    }

    /**
     * @return array<int, array{handler: string, parameter: string, projectionName: string}>
     */
    private static function parametersRequiringProjectionName(InterfaceToCallRegistry $interfaceToCallRegistry, string $className, string $methodName, string $projectionName): array
    {
        $required = [];
        foreach ($interfaceToCallRegistry->getFor($className, $methodName)->getInterfaceParameters() as $parameter) {
            if ($parameter->hasAnnotation(ProjectionName::class) && ! $parameter->doesAllowNulls() && ! $parameter->hasDefaultValue()) {
                $required[] = ['handler' => $className . '::' . $methodName, 'parameter' => $parameter->getName(), 'projectionName' => $projectionName];
            }
        }

        return $required;
    }

    private static function flushMethodUsesProjectionState(string $className, string $methodName): bool
    {
        $reflectionMethod = new ReflectionMethod($className, $methodName);
        foreach ($reflectionMethod->getParameters() as $parameter) {
            foreach ($parameter->getAttributes(Header::class, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
                if ($attribute->newInstance()->getHeaderName() === ProjectingHeaders::PROJECTION_STATE) {
                    return true;
                }
            }
        }
        return false;
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
