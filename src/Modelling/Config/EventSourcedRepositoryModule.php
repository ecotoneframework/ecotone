<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\Modelling\Config;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Api\Attribute\ModuleAnnotation;
use Ecotone\Api\ExtensionObject\ModulePackageList;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\EventSourcing\EventSourcedRepositoryAdapterBuilder;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Modelling\BaseEventSourcingConfiguration;

#[ModuleAnnotation]
final class EventSourcedRepositoryModule implements AnnotationModule
{
    public static function create(AnnotationFinder $annotationRegistrationService, InterfaceToCallRegistry $interfaceToCallRegistry): static
    {
        return new self();
    }

    public function prepare(Configuration $messagingConfiguration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
    }

    public function getModuleExtensions(ServiceConfiguration $serviceConfiguration, array $serviceExtensions): array
    {
        $baseEventSourcingConfiguration = BaseEventSourcingConfiguration::withDefaults();
        foreach ($serviceExtensions as $moduleExtension) {
            if ($moduleExtension instanceof BaseEventSourcingConfiguration) {
                $baseEventSourcingConfiguration = $moduleExtension;
            }
        }

        return [new EventSourcedRepositoryAdapterBuilder($baseEventSourcingConfiguration)];
    }

    public function getModulePackageName(): string
    {
        return ModulePackageList::CORE_PACKAGE;
    }
}
