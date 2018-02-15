<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Config\Annotation;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\ModuleConfigurationAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ModuleConfigurationExtensionAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationException;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationVariableRetrievingService;
use SimplyCodedSoftware\IntegrationMessaging\Config\InMemoryConfigurationVariableRetrievingService;
use SimplyCodedSoftware\IntegrationMessaging\Config\ModuleConfigurationExtension;
use SimplyCodedSoftware\IntegrationMessaging\Config\ModuleConfigurationRetrievingService;

/**
 * Class AnnotationModuleConfigurationRetrievingService
 * @package SimplyCodedSoftware\IntegrationMessaging\Config\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AnnotationModuleConfigurationRetrievingService implements ModuleConfigurationRetrievingService
{
    /**
     * @var ClassLocator
     */
    private $classLocator;
    /**
     * @var ClassMetadataReader
     */
    private $classMetadataReader;
    /**
     * @var ConfigurationVariableRetrievingService
     */
    private $configurationVariableRetrievingService;

    /**
     * AnnotationModuleConfigurationRetrievingService constructor.
     * @param ConfigurationVariableRetrievingService $configurationVariableRetrievingService
     * @param ClassLocator $classLocator
     * @param ClassMetadataReader $classMetadataReader
     */
    public function __construct(ConfigurationVariableRetrievingService $configurationVariableRetrievingService, ClassLocator $classLocator, ClassMetadataReader $classMetadataReader)
    {
        $this->configurationVariableRetrievingService = $configurationVariableRetrievingService;
        $this->classLocator = $classLocator;
        $this->classMetadataReader = $classMetadataReader;
    }

    /**
     * @inheritDoc
     */
    public function findAllModuleConfigurations(): array
    {
        /** @var AnnotationConfiguration[]|string[] $configurationClassNames */
        $configurationClassNames = $this->classLocator->getAllClassesWithAnnotation(ModuleConfigurationAnnotation::class);
        $configurationClasses = [];
        /** @var ModuleConfigurationExtension[]|string[] $configurationExtensionClassNames */
        $configurationExtensionClassNames = $this->classLocator->getAllClassesWithAnnotation(ModuleConfigurationExtensionAnnotation::class);
        $configurationExtensions = [];

        foreach ($configurationExtensionClassNames as $configurationExtensionClassName) {
            /** @var ModuleConfigurationExtensionAnnotation $annotation */
            $annotation = $this->classMetadataReader->getAnnotationForClass($configurationExtensionClassName, ModuleConfigurationExtensionAnnotation::class);
            $configurationVariables = $this->getConfigurationVariablesForAnnotation($annotation);

            if (!is_subclass_of($configurationExtensionClassName, ModuleConfigurationExtension::class)) {
                throw ConfigurationException::create("Can't register module extension {$configurationExtensionClassName} it doesn't extend " . ModuleConfigurationExtension::class);
            }

            $configurationExtensions[$annotation->moduleName][] = $configurationExtensionClassName::create(InMemoryConfigurationVariableRetrievingService::create($configurationVariables));
        }

        foreach ($configurationClassNames as $configurationClassName) {
            /** @var ModuleConfigurationAnnotation $annotation */
            $annotation = $this->classMetadataReader->getAnnotationForClass($configurationClassName, ModuleConfigurationAnnotation::class);
            $configurationVariables = $this->getConfigurationVariablesForAnnotation($annotation);

            if (!is_subclass_of($configurationClassName, AnnotationConfiguration::class)) {
                throw ConfigurationException::create("Can't register module {$configurationClassName} it doesn't extend " . AnnotationConfiguration::class);
            }

            $extensionsForConfiguration = array_key_exists($annotation->moduleName, $configurationExtensions) ? $configurationExtensions[$annotation->moduleName] : [];
            $configurationClasses[] = $configurationClassName::createAnnotationConfiguration($extensionsForConfiguration, InMemoryConfigurationVariableRetrievingService::create($configurationVariables), $this->classLocator, $this->classMetadataReader);
        }

        return $configurationClasses;
    }

    /**
     * @param $annotation
     * @return array
     * @throws ConfigurationException
     */
    private function getConfigurationVariablesForAnnotation($annotation): array
    {
        $configurationVariables = [];
        foreach ($annotation->variables as $variable) {
            if (!$this->configurationVariableRetrievingService->has($variable->variableName)) {
                if ($variable->defaultValue) {
                    $configurationVariables[$variable->variableName] = $variable->defaultValue;
                    continue;
                }

                throw ConfigurationException::create("Configuration '{$variable->variableName}' for module '{$annotation->moduleName}' is required to be set");
            }

            $configurationVariables[$variable->variableName] = $this->configurationVariableRetrievingService->get($variable->variableName);
        }

        return $configurationVariables;
    }
}