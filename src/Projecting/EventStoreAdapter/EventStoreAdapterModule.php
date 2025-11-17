<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Ecotone\Projecting\EventStoreAdapter;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Attribute\ModuleAnnotation;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Endpoint\InboundChannelAdapter\InboundChannelAdapterBuilder;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Projecting\Config\ProjectingModule;

/**
 * @internal
 * licence Enterprise
 */
#[ModuleAnnotation]
class EventStoreAdapterModule implements AnnotationModule
{
    public function __construct(
        private array $extensions = []
    ) {
    }

    public static function create(AnnotationFinder $annotationRegistrationService, InterfaceToCallRegistry $interfaceToCallRegistry): static
    {
        return new self();
    }

    public function prepare(Configuration $messagingConfiguration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        // Collect EventStoreChannelAdapter extension objects
        $channelAdapters = [];
        foreach ($extensionObjects as $extensionObject) {
            if ($extensionObject instanceof EventStoreChannelAdapter) {
                $channelAdapters[] = $extensionObject;
            }
        }

        // Register inbound channel adapters (polling endpoints) for each channel adapter
        foreach ($channelAdapters as $channelAdapter) {
            $messagingConfiguration->registerConsumer(
                InboundChannelAdapterBuilder::createWithDirectObject(
                    ProjectingModule::inputChannelForProjectingManager($channelAdapter->getProjectionName()),
                    new PollingProjectionChannelAdapter(),
                    $interfaceToCallRegistry->getFor(PollingProjectionChannelAdapter::class, 'execute')
                )
                    ->withEndpointId($channelAdapter->endpointId)
            );
        }
    }

    public function canHandle($extensionObject): bool
    {
        return $extensionObject instanceof EventStoreChannelAdapter;
    }

    public function getModuleExtensions(ServiceConfiguration $serviceConfiguration, array $serviceExtensions): array
    {
        $extensions = [...$this->extensions];

        foreach ($serviceExtensions as $extensionObject) {
            if (! ($extensionObject instanceof EventStoreChannelAdapter)) {
                continue;
            }

            $extensions[] = new EventStoreChannelAdapterProjectionBuilder($extensionObject);
        }

        return $extensions;
    }

    public function getModulePackageName(): string
    {
        return ModulePackageList::CORE_PACKAGE;
    }
}
