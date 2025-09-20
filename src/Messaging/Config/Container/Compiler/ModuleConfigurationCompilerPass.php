<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config\Container\Compiler;

use Ecotone\Messaging\Config\Container\ContainerBuilder;
use Ecotone\Messaging\Config\MessagingSystemConfiguration;
use Ecotone\Messaging\Config\Module;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Config\ServiceConfiguration;

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
        $extensionObjects = $this->extensionObjects;
        foreach ($this->modules as $module) {
            $extensionObjects = array_merge($extensionObjects, $module->getModuleExtensions($this->serviceConfiguration, $this->extensionObjects, $this->configuration->getInterfaceToCallRegistry()));
        }

        $moduleExtensions = [];
        foreach ($this->modules as $module) {
            $moduleExtensions[get_class($module)] = [];
            foreach ($extensionObjects as $extensionObject) {
                if ($module->canHandle($extensionObject)) {
                    $moduleExtensions[get_class($module)][] = $extensionObject;
                }
            }
        }

        foreach ($this->modules as $module) {
            $module->prepare(
                $this->configuration,
                $moduleExtensions[get_class($module)],
                $this->moduleReferenceSearchService,
                $this->configuration->getInterfaceToCallRegistry(),
            );
        }
    }
}
