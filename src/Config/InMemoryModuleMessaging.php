<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Config;
use SimplyCodedSoftware\IntegrationMessaging\Support\Assert;

/**
 * Class InMemoryModuleMessagingConfiguration
 * @package SimplyCodedSoftware\IntegrationMessaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InMemoryModuleMessaging implements ModuleRetrievingService
{
    /**
     * @var array|Module[]
     */
    private $moduleConfigurations = [];
    /**
     * @var array
     */
    private $extensionObjects;

    /**
     * InMemoryModuleMessagingConfiguration constructor.
     * @param array|Module[] $moduleMessagingConfigurations
     * @param array $extensionObjects
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    private function __construct(array $moduleMessagingConfigurations, array $extensionObjects)
    {
        Assert::allInstanceOfType($moduleMessagingConfigurations, Module::class);

        $this->moduleConfigurations = $moduleMessagingConfigurations;
        $this->extensionObjects = $extensionObjects;
    }

    /**
     * @return InMemoryModuleMessaging
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public static function createEmpty() : self
    {
        return new self([], []);
    }

    /**
     * @param array $moduleConfigurations
     * @param array $moduleExtensions
     * @return InMemoryModuleMessaging
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public static function createWith(array $moduleConfigurations, array $moduleExtensions) : self
    {
        return new self($moduleConfigurations, $moduleExtensions);
    }

    /**
     * @inheritDoc
     */
    public function findAllModuleConfigurations(): array
    {
        return $this->moduleConfigurations;
    }

    /**
     * @inheritDoc
     */
    public function findAllExtensionObjects(): array
    {
        return $this->extensionObjects;
    }
}