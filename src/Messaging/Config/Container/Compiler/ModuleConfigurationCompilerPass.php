<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config\Container\Compiler;

use Ecotone\Api\ExtensionObject\ModulePackageList;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Lite\Test\Configuration\ProvidedReferences;
use Ecotone\Lite\Test\Configuration\ResolvesExtensionObjectsForFlowTesting;
use Ecotone\Messaging\Config\Container\ContainerBuilder;
use Ecotone\Messaging\Config\MessagingSystemConfiguration;
use Ecotone\Messaging\Config\Module;
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
    private function resolveForFlowTesting(array $extensionObjects): array
    {
        $externalContainer = $this->configuration->getExternalContainer();
        if ($externalContainer === null || ! $this->serviceConfiguration->isModulePackageEnabled(ModulePackageList::TEST_PACKAGE)) {
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
