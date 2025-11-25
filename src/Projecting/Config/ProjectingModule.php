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
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\HeaderBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\ValueBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvokerBuilder;
use Ecotone\Messaging\Handler\ServiceActivator\MessageProcessorActivatorBuilder;
use Ecotone\Projecting\InMemory\InMemoryProjectionRegistry;
use Ecotone\Projecting\InMemory\InMemoryProjectionStateStorage;
use Ecotone\Projecting\NullPartitionProvider;
use Ecotone\Projecting\PartitionProvider;
use Ecotone\Projecting\ProjectingHeaders;
use Ecotone\Projecting\ProjectingManager;
use Ecotone\Projecting\ProjectionRegistry;
use Ecotone\Projecting\ProjectionStateStorage;
use Ecotone\Projecting\StreamSource;
use Ramsey\Uuid\Uuid;

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
        $componentBuilders = ExtensionObjectResolver::resolve(ProjectionComponentBuilder::class, $extensionObjects);

        if (! empty($projectionBuilders) && ! $messagingConfiguration->isRunningForEnterpriseLicence()) {
            throw ConfigurationException::create('Projections are part of Ecotone Enterprise. To use projections, please acquire an enterprise licence.');
        }

        /** @var array<string, array<string, string>> $components [projection name][component name][reference] */
        $components = [];
        foreach ($componentBuilders as $componentBuilder) {
            $reference = Uuid::uuid4()->toString();
            $moduleReferenceSearchService->store($reference, $componentBuilder);
            foreach ($projectionBuilders as $projectionBuilder) {
                $projectionName = $projectionBuilder->projectionName();
                foreach ([StreamSource::class, PartitionProvider::class, ProjectionStateStorage::class] as $component) {
                    if ($componentBuilder->canHandle($projectionName, $component)) {
                        if (isset($components[$projectionName][$component])) {
                            throw ConfigurationException::create(
                                "Projection with name {$projectionName} is already registered for component {$component} with reference {$components[$projectionName][$component]}."
                                . ' You can only register one component of each type per projection. Please check your configuration.'
                            );
                        }
                        $components[$projectionName][$component] = new Reference($reference);
                    }
                }
            }
        }

        $projectionRegistryMap = [];
        foreach ($projectionBuilders as $projectionBuilder) {
            $projectionName = $projectionBuilder->projectionName();
            $reference = self::getProjectorExecutorReference($projectionName);
            $moduleReferenceSearchService->store($reference, $projectionBuilder);

            $messagingConfiguration->registerServiceDefinition(
                $projectingManagerReference = ProjectingManager::class . ':' . $projectionName,
                new Definition(ProjectingManager::class, [
                    $components[$projectionName][ProjectionStateStorage::class] ?? new Definition(InMemoryProjectionStateStorage::class),
                    new Reference($reference),
                    $components[$projectionName][StreamSource::class] ?? throw ConfigurationException::create("Projection with name {$projectionName} does not have stream source configured. Please check your configuration."),
                    $components[$projectionName][PartitionProvider::class] ?? new Definition(NullPartitionProvider::class),
                    $projectionName,
                    $projectionBuilder->batchSize(), // batchSize
                    $projectionBuilder->automaticInitialization(),
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
                                    ? HeaderBuilder::create('partitionKey', $projectionBuilder->partitionHeader())
                                    : ValueBuilder::create('partitionKey', null),
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

        // Register console commands
        $messagingConfiguration->registerServiceDefinition(ProjectingConsoleCommands::class, new Definition(ProjectingConsoleCommands::class, [new Reference(ProjectionRegistry::class)]));
    }

    public function canHandle($extensionObject): bool
    {
        return $extensionObject instanceof ServiceConfiguration
            || $extensionObject instanceof ProjectionExecutorBuilder
            || $extensionObject instanceof ProjectionComponentBuilder;
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
