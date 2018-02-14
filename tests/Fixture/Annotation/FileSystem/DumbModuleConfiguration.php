<?php

namespace Fixture\Annotation\FileSystem;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\ModuleConfigurationAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ClassLocator;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ClassMetadataReader;
use SimplyCodedSoftware\IntegrationMessaging\Config\Configuration;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationVariableRetrievingService;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfiguredMessagingSystem;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationConfiguration;

/**
 * Class DumbModuleConfiguration
 * @package Fixture\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ModuleConfigurationAnnotation(moduleName="dumb-configuration")
 */
class DumbModuleConfiguration implements AnnotationConfiguration
{
    /**
     * @inheritDoc
     */
    public static function createAnnotationConfiguration(ConfigurationVariableRetrievingService $configurationVariableRetrievingService, ClassLocator $classLocator, ClassMetadataReader $classMetadataReader): AnnotationConfiguration
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function registerWithin(Configuration $configuration, ConfigurationVariableRetrievingService $configurationVariableRetrievingService): void
    {

    }

    /**
     * @inheritDoc
     */
    public function postConfigure(ConfiguredMessagingSystem $configuredMessagingSystem): void
    {
        // TODO: Implement postConfigure() method.
    }
}