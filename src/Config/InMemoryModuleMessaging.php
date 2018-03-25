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
     * @var array|ModuleExtension[]
     */
    private $moduleExtensions;

    /**
     * InMemoryModuleMessagingConfiguration constructor.
     * @param array|Module[] $moduleMessagingConfigurations
     * @param ModuleExtension[] $moduleExtensions
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    private function __construct(array $moduleMessagingConfigurations, array $moduleExtensions)
    {
        Assert::allInstanceOfType($moduleMessagingConfigurations, Module::class);
        Assert::allInstanceOfType($moduleExtensions, ModuleExtension::class);

        $this->moduleConfigurations = $moduleMessagingConfigurations;
        $this->moduleExtensions = $moduleExtensions;
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
    public function findAllModuleExtensionConfigurations(): array
    {
        return $this->moduleExtensions;
    }
}