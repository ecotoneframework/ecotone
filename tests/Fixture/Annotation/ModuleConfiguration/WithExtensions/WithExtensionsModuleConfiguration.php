<?php

namespace Fixture\Annotation\ModuleConfiguration\WithExtensions;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\ModuleConfigurationAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationConfiguration;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ClassLocator;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ClassMetadataReader;
use SimplyCodedSoftware\IntegrationMessaging\Config\Configuration;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationVariableRetrievingService;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfiguredMessagingSystem;
use SimplyCodedSoftware\IntegrationMessaging\Config\ModuleConfigurationExtension;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ConfigurationVariableAnnotation;

/**
 * Class WithExtensionsModuleConfiguration
 * @package Fixture\Annotation\ModuleConfiguration\WithExtensions
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ModuleConfigurationAnnotation(moduleName="module-with-extension-configuration", variables={
 *     @ConfigurationVariableAnnotation(variableName="system", description="system")
 * })
 */
class WithExtensionsModuleConfiguration implements AnnotationConfiguration
{
    /**
     * @var SimpleExtensionModuleConfiguration[]
     */
    private $extensions;
    /**
     * @var array
     */
    private $variables;

    /**
     * @inheritDoc
     */
    public static function createAnnotationConfiguration(array $moduleConfigurationExtensions, ConfigurationVariableRetrievingService $configurationVariableRetrievingService, ClassLocator $classLocator, ClassMetadataReader $classMetadataReader): AnnotationConfiguration
    {
        $configuration = new self();
        $configuration->extensions = $moduleConfigurationExtensions;
        $configuration->variables = ["system" => $configurationVariableRetrievingService->get("system")];

        return $configuration;
    }

    public function getExtensions() : array
    {
        return $this->extensions;
    }

    public function getVariables() : array
    {
        return $this->variables;
    }

    /**
     * @inheritDoc
     */
    public function registerWithin(Configuration $configuration, ConfigurationVariableRetrievingService $configurationVariableRetrievingService): void
    {
        // TODO: Implement registerWithin() method.
    }

    /**
     * @inheritDoc
     */
    public function postConfigure(ConfiguredMessagingSystem $configuredMessagingSystem): void
    {
        return;
    }
}