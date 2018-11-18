<?php

namespace Fixture\Configuration;

use SimplyCodedSoftware\IntegrationMessaging\Config\ModuleExtension;
use SimplyCodedSoftware\IntegrationMessaging\Config\ModuleRetrievingService;


/**
 * Class DumbModuleConfigurationRetrievingService
 * @package Fixture\Configuration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class DumbModuleRetrievingService implements ModuleRetrievingService
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
    public function findAllExtensionObjects(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function findAllModuleConfigurations(): array
    {
        return [];
    }
}