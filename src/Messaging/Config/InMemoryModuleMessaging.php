<?php

namespace Ecotone\Messaging\Config;
use Ecotone\Messaging\Support\Assert;

/**
 * Class InMemoryModuleMessagingConfiguration
 * @package Ecotone\Messaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InMemoryModuleMessaging implements ModuleRetrievingService
{
    private array $moduleConfigurations = [];
    private array $extensionObjects;

    /**
     * InMemoryModuleMessagingConfiguration constructor.
     * @param array|Module[] $moduleMessagingConfigurations
     * @param array $extensionObjects
     * @throws \Ecotone\Messaging\MessagingException
     */
    private function __construct(array $moduleMessagingConfigurations, array $extensionObjects)
    {
        Assert::allInstanceOfType($moduleMessagingConfigurations, Module::class);

        $this->moduleConfigurations = $moduleMessagingConfigurations;
        $this->extensionObjects = $extensionObjects;
    }

    /**
     * @return InMemoryModuleMessaging
     * @throws \Ecotone\Messaging\MessagingException
     */
    public static function createEmpty() : self
    {
        return new self([], []);
    }

    /**
     * @param Module[] $moduleConfigurations
     * @param object[] $moduleExtensions
     * @return InMemoryModuleMessaging
     * @throws \Ecotone\Messaging\MessagingException
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