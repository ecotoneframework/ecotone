<?php

namespace Fixture\Annotation\ModuleConfiguration\WrongVariables;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\ModuleConfigurationAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationConfiguration;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ClassLocator;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ClassMetadataReader;
use SimplyCodedSoftware\IntegrationMessaging\Config\Configuration;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationVariableRetrievingService;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfiguredMessagingSystem;
use SimplyCodedSoftware\IntegrationMessaging\Config\ModuleConfigurationExtension;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ConfigurationVariableAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;

/**
 * Class UsingVariablesNotDefinedInModuleConfiguration
 * @package Fixture\Annotation\ModuleConfiguration\WrongVariables
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 *
 * @ModuleConfigurationAnnotation(moduleName="with-variables-module-configuration", variables={
 *      @ConfigurationVariableAnnotation(variableName="token", description="token variable")
 * })
 */
class UsingVariablesNotDefinedInModuleConfiguration implements AnnotationConfiguration
{
    /**
     * @inheritDoc
     */
    public static function createAnnotationConfiguration(array $moduleConfigurationExtensions, ConfigurationVariableRetrievingService $configurationVariableRetrievingService, ClassLocator $classLocator, ClassMetadataReader $classMetadataReader): AnnotationConfiguration
    {
        $configurationVariableRetrievingService->get("autologin");
    }

    /**
     * @inheritDoc
     */
    public function configure(ReferenceSearchService $referenceSearchService): void
    {
        return;
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
}