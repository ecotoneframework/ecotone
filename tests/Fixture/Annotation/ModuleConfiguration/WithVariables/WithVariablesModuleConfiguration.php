<?php

namespace Fixture\Annotation\ModuleConfiguration\WithVariables;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\ConfigurationVariableAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ModuleConfigurationAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationConfiguration;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ClassLocator;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ClassMetadataReader;
use SimplyCodedSoftware\IntegrationMessaging\Config\Configuration;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationVariableRetrievingService;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfiguredMessagingSystem;

/**
 * Class WithVariablesModuleConfiguration
 * @package Fixture\Annotation\ModuleConfiguration\WithVariables
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ModuleConfigurationAnnotation(moduleName="with-variables-module-configuration", variables={
 *      @ConfigurationVariableAnnotation(variableName="token", description="token variable"),
 *      @ConfigurationVariableAnnotation(variableName="autologout", defaultValue="true", description="is auto logged off")
 * })
 */
class WithVariablesModuleConfiguration implements AnnotationConfiguration
{
    /**
     * @var array
     */
    private $variables;

    /**
     * WithVariablesModuleConfiguration constructor.
     * @param array $variables
     */
    private function __construct(array $variables)
    {
        $this->variables = $variables;
    }

    /**
     * @inheritDoc
     */
    public static function createAnnotationConfiguration(array $moduleConfigurationExtensions, ConfigurationVariableRetrievingService $configurationVariableRetrievingService, ClassLocator $classLocator, ClassMetadataReader $classMetadataReader): AnnotationConfiguration
    {
        return new self([
            "token" => $configurationVariableRetrievingService->get("token"),
            "autologout" => $configurationVariableRetrievingService->get("autologout")
        ]);
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
        // TODO: Implement postConfigure() method.
    }

    public function getVariables() : array
    {
        return $this->variables;
    }
}