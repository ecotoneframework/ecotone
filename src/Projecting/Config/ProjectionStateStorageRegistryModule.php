<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Ecotone\Projecting\Config;

use function array_map;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Attribute\ModuleAnnotation;
use Ecotone\Messaging\Config\Annotation\AnnotatedDefinitionReference;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ExtensionObjectResolver;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\NoExternalConfigurationModule;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Projecting\Attribute\ProjectionV2;
use Ecotone\Projecting\Attribute\StateStorage as StateStorageAttribute;
use Ecotone\Projecting\InMemory\InMemoryProjectionStateStorage;
use Ecotone\Projecting\ProjectionStateStorageReference;
use Ecotone\Projecting\ProjectionStateStorageRegistry;

#[ModuleAnnotation]
class ProjectionStateStorageRegistryModule extends NoExternalConfigurationModule implements AnnotationModule
{
    /**
     * @param string[] $allProjectionNames
     * @param string[] $userlandStateStorageReferences
     */
    public function __construct(
        private array $allProjectionNames = [],
        private array $userlandStateStorageReferences = [],
    ) {
    }

    public static function create(AnnotationFinder $annotationFinder, InterfaceToCallRegistry $interfaceToCallRegistry): static
    {
        $allProjectionNames = [];
        foreach ($annotationFinder->findAnnotatedClasses(ProjectionV2::class) as $projectionClassName) {
            $projectionAttribute = $annotationFinder->getAttributeForClass($projectionClassName, ProjectionV2::class);
            $allProjectionNames[] = $projectionAttribute->name;
        }

        $userlandStateStorageReferences = [];
        foreach ($annotationFinder->findAnnotatedClasses(StateStorageAttribute::class) as $storageClassName) {
            $userlandStateStorageReferences[] = AnnotatedDefinitionReference::getReferenceForClassName($annotationFinder, $storageClassName);
        }

        return new self($allProjectionNames, $userlandStateStorageReferences);
    }

    public function prepare(Configuration $messagingConfiguration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        $stateStorageReferences = ExtensionObjectResolver::resolve(
            ProjectionStateStorageReference::class,
            $extensionObjects
        );

        $userlandStorages = array_map(
            fn (string $reference) => new Reference($reference),
            $this->userlandStateStorageReferences
        );

        $builtinStorages = array_map(
            fn (ProjectionStateStorageReference $ref) => new Reference($ref->getReferenceName()),
            $stateStorageReferences
        );

        $messagingConfiguration->registerServiceDefinition(
            InMemoryProjectionStateStorage::class,
            new Definition(InMemoryProjectionStateStorage::class, [null])
        );

        $messagingConfiguration->registerServiceDefinition(
            ProjectionStateStorageRegistry::class,
            new Definition(ProjectionStateStorageRegistry::class, [
                $userlandStorages,
                $builtinStorages,
            ])
        );
    }

    public function canHandle($extensionObject): bool
    {
        return $extensionObject instanceof ProjectionStateStorageReference;
    }

    public function getModulePackageName(): string
    {
        return ModulePackageList::CORE_PACKAGE;
    }
}
