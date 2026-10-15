<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\Projecting\Config;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Api\Attribute\ModuleAnnotation;
use Ecotone\Api\ExtensionObject\ModulePackageList;
use Ecotone\Api\Projecting\StreamSource as StreamSourceAttribute;
use Ecotone\Messaging\Config\Annotation\AnnotatedDefinitionReference;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ExtensionObjectResolver;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\NoExternalConfigurationModule;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Support\LicensingException;
use Ecotone\Projecting\StreamSourceReference;
use Ecotone\Projecting\StreamSourceRegistry;

#[ModuleAnnotation]
final class StreamSourceRegistryModule extends NoExternalConfigurationModule implements AnnotationModule
{
    /**
     * @param string[] $userlandStreamSourceReferences
     */
    public function __construct(
        private array $userlandStreamSourceReferences = [],
    ) {
    }

    public static function create(AnnotationFinder $annotationFinder, InterfaceToCallRegistry $interfaceToCallRegistry): static
    {
        $userlandStreamSourceReferences = [];
        foreach ($annotationFinder->findAnnotatedClasses(StreamSourceAttribute::class) as $sourceClassName) {
            $userlandStreamSourceReferences[] = AnnotatedDefinitionReference::getReferenceForClassName($annotationFinder, $sourceClassName);
        }

        return new self($userlandStreamSourceReferences);
    }

    public function prepare(Configuration $messagingConfiguration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        if (! empty($this->userlandStreamSourceReferences) && ! $messagingConfiguration->isRunningForEnterpriseLicence()) {
            throw LicensingException::create('Custom #[StreamSource] implementations require Ecotone Enterprise licence. See https://docs.ecotone.tech/enterprise for details.');
        }

        $streamSourceReferences = ExtensionObjectResolver::resolve(
            StreamSourceReference::class,
            $extensionObjects
        );

        $userlandSources = array_map(
            fn (string $reference) => new Reference($reference),
            $this->userlandStreamSourceReferences
        );

        $builtinSources = array_map(
            fn (StreamSourceReference $ref) => new Reference($ref->getReferenceName()),
            $streamSourceReferences
        );

        $messagingConfiguration->registerServiceDefinition(
            StreamSourceRegistry::class,
            new Definition(StreamSourceRegistry::class, [
                $userlandSources,
                $builtinSources,
            ])
        );
    }

    public function getModulePackageName(): string
    {
        return ModulePackageList::CORE_PACKAGE;
    }
}
