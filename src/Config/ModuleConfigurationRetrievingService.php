<?php

namespace SimplyCodedSoftware\Messaging\Config;

/**
 * Interface ModuleConfigurationRetrievingService
 * @package SimplyCodedSoftware\Messaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ModuleConfigurationRetrievingService
{
    /**
     * @return array|ModuleMessagingConfiguration[]
     */
    public function findAllModuleConfigurations() : array;
}