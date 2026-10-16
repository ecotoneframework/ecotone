<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config\Container\Compiler;

use Ecotone\Api\ExtensionObject\ModulePackageList;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Lite\Test\Configuration\ProvidedReferences;
use Ecotone\Lite\Test\Configuration\ResolvesExtensionObjectsForFlowTesting;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ExtensionObjectResolver;
use Ecotone\Messaging\Config\BootstrapConfiguration;
use Ecotone\Messaging\Config\Container\ContainerBuilder;
use Ecotone\Messaging\Config\MessagingSystemConfiguration;
use Ecotone\Messaging\Config\Module;
use Ecotone\Messaging\Config\ModuleDefaultSerializationMediaType;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;

/**
 * licence Apache-2.0
 */
final class ModuleConfigurationCompilerPass implements CompilerPass
{
    /**
     * @param Module[] $modules
     * @param object[] $extensionObjects
     */
    public function __construct(
        private array $modules,
        private $extensionObjects,
        private ServiceConfiguration $serviceConfiguration,
        private BootstrapConfiguration $bootstrapConfiguration,
        private MessagingSystemConfiguration $configuration,
        private ModuleReferenceSearchService $moduleReferenceSearchService
    ) {

    }

    public function process(ContainerBuilder $builder): void
    {
        $serviceExtensions = $this->resolveForFlowTesting($this->extensionObjects);
        $extensionObjects = $serviceExtensions;
        foreach ($this->modules as $module) {
            $extensionObjects = array_merge($extensionObjects, $module->getModuleExtensions($this->serviceConfiguration, $serviceExtensions, $this->configuration->getInterfaceToCallRegistry()));
        }

        $extensionObjects = $this->withResolvedSerializationMediaType($extensionObjects);

        foreach ($this->modules as $module) {
            $module->prepare(
                $this->configuration,
                $extensionObjects,
                $this->moduleReferenceSearchService,
                $this->configuration->getInterfaceToCallRegistry(),
            );
        }
    }

    /**
     * @param object[] $extensionObjects
     * @return object[]
     */
    private function withResolvedSerializationMediaType(array $extensionObjects): array
    {
        if ($this->serviceConfiguration->defaultSerializationMediaType !== null) {
            return $extensionObjects;
        }

        $moduleDefaults = ExtensionObjectResolver::resolve(ModuleDefaultSerializationMediaType::class, $extensionObjects);
        $serviceConfiguration = $this->serviceConfiguration->withDefaultSerializationMediaType($moduleDefaults[0]->mediaType ?? ServiceConfiguration::DEFAULT_SERIALIZATION_MEDIA_TYPE);
        $this->configuration->replaceServiceConfiguration($serviceConfiguration);

        return array_map(
            fn (object $extensionObject): object => $extensionObject instanceof ServiceConfiguration ? $serviceConfiguration : $extensionObject,
            $extensionObjects,
        );
    }

    /**
     * @param object[] $extensionObjects
     * @return object[]
     */
    private function resolveForFlowTesting(array $extensionObjects): array
    {
        $externalContainer = $this->configuration->getExternalContainer();
        if ($externalContainer === null || ! $this->bootstrapConfiguration->isModulePackageEnabled(ModulePackageList::TEST_PACKAGE)) {
            return $extensionObjects;
        }

        foreach ($this->modules as $module) {
            if ($module instanceof ResolvesExtensionObjectsForFlowTesting) {
                $extensionObjects = $module->resolveForFlowTesting($extensionObjects, new ProvidedReferences($externalContainer, $extensionObjects));
            }
        }

        return $extensionObjects;
    }
}
