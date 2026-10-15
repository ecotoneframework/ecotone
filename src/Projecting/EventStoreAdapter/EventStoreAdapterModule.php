<?php

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
declare(strict_types=1);

namespace Ecotone\Projecting\EventStoreAdapter;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Api\Attribute\ModuleAnnotation;
use Ecotone\Api\ExtensionObject\ModulePackageList;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\Projecting\EventStreamingChannelAdapter;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Endpoint\InboundChannelAdapter\InboundChannelAdapterBuilder;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Projecting\Config\ProjectingModule;

/**
 * @internal
 * licence Enterprise
 */
#[ModuleAnnotation]
final class EventStoreAdapterModule implements AnnotationModule
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
            if ($extensionObject instanceof EventStreamingChannelAdapter) {
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

    public function getModuleExtensions(ServiceConfiguration $serviceConfiguration, array $serviceExtensions): array
    {
        $extensions = [...$this->extensions];

        foreach ($serviceExtensions as $extensionObject) {
            if (! ($extensionObject instanceof EventStreamingChannelAdapter)) {
                continue;
            }

            $extensions[] = new EventStoreStreamingChannelAdapterBuilder($extensionObject);
        }

        return $extensions;
    }

    public function getModulePackageName(): string
    {
        return ModulePackageList::CORE_PACKAGE;
    }
}
