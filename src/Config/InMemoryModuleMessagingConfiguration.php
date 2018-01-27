<?php

namespace SimplyCodedSoftware\Messaging\Config;

/**
 * Class InMemoryModuleMessagingConfiguration
 * @package SimplyCodedSoftware\Messaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InMemoryModuleMessagingConfiguration implements ModuleConfigurationRetrievingService
{
    /**
     * @var array|ModuleMessagingConfiguration[]
     */
    private $moduleConfigurations = [];
    /**
     * @var array
     */
    private $moduleMessagingConfigurations;

    /**
     * InMemoryModuleMessagingConfiguration constructor.
     * @param array|ModuleMessagingConfiguration[] $moduleMessagingConfigurations
     */
    private function __construct(array $moduleMessagingConfigurations)
    {
        $this->moduleMessagingConfigurations = $moduleMessagingConfigurations;
    }

    /**
     * @return InMemoryModuleMessagingConfiguration
     */
    public static function createEmpty() : self
    {
        return new self([]);
    }

    /**
     * @inheritDoc
     */
    public function findAllModuleConfigurations(): array
    {
        return $this->moduleConfigurations;
    }
}