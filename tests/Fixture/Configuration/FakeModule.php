<?php

namespace Fixture\Configuration;

use SimplyCodedSoftware\IntegrationMessaging\Config\Configuration;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationObserver;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationVariableRetrievingService;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfiguredMessagingSystem;
use SimplyCodedSoftware\IntegrationMessaging\Config\Module;
use SimplyCodedSoftware\IntegrationMessaging\Config\ModuleExtension;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;

/**
 * Class FakeModuleConfiguration
 * @package Fixture\Configuration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class FakeModule implements Module
{
    /**
     * @var ModuleExtension[]
     */
    private $moduleExtensions;


    public static function create() : self
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return "fake";
    }

    /**
     * @inheritDoc
     */
    public function getConfigurationVariables(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferences(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function preConfigure(array $moduleExtensions, ConfigurationObserver $configurationObserver) : void
    {
        // TODO: Implement preConfigure() method.
    }

    /**
     * @inheritDoc
     */
    public function registerWithin(Configuration $configuration, array $moduleExtensions, ConfigurationVariableRetrievingService $configurationVariableRetrievingService, ReferenceSearchService $referenceSearchService): void
    {
        $this->moduleExtensions = $moduleExtensions;
    }

    /**
     * @param array $extensions
     * @return FakeModule
     */
    public static function createWithExtensions(array $extensions) : self
    {
        $fakeModuleConfiguration = new self();
        $fakeModuleConfiguration->moduleExtensions = $extensions;

        return $fakeModuleConfiguration;
    }

    /**
     * @inheritDoc
     */
    public function postConfigure(ConfiguredMessagingSystem $configuredMessagingSystem): void
    {
        return;
    }
}