<?php

namespace Fixture\Configuration;

use SimplyCodedSoftware\Messaging\Config\ModuleConfigurationRetrievingService;
use SimplyCodedSoftware\Messaging\Config\ModuleMessagingConfiguration;

/**
 * Class DumbModuleConfigurationRetrievingService
 * @package Fixture\Configuration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class DumbModuleConfigurationRetrievingService implements ModuleConfigurationRetrievingService
{
    private function __construct()
    {
    }

    public static function createEmpty() : self
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function findAllModuleConfigurations(): array
    {
        return [];
    }
}