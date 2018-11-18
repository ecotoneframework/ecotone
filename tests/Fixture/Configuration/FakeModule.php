<?php

namespace Fixture\Configuration;

use SimplyCodedSoftware\IntegrationMessaging\Config\Configuration;
use SimplyCodedSoftware\IntegrationMessaging\Config\Module;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;

/**
 * Class FakeModuleConfiguration
 * @package Fixture\Configuration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class FakeModule implements Module
{
    /**
     * @var object[]
     */
    private $moduleExtensions;


    public static function create(): self
    {
        return new self();
    }

    /**
     * @param array $extensions
     * @return FakeModule
     */
    public static function createWithExtensions(array $extensions): self
    {
        $fakeModuleConfiguration = new self();
        $fakeModuleConfiguration->moduleExtensions = $extensions;

        return $fakeModuleConfiguration;
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
    public function prepare(Configuration $configuration, array $extensionObjects): void
    {
        $this->moduleExtensions = $extensionObjects;
    }

    /**
     * @inheritDoc
     */
    public function canHandle($extensionObject): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function afterConfigure(ReferenceSearchService $referenceSearchService): void
    {

    }
}