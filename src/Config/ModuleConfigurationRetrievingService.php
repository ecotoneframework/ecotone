<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Config;

/**
 * Interface ModuleConfigurationRetrievingService
 * @package SimplyCodedSoftware\IntegrationMessaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ModuleConfigurationRetrievingService
{
    /**
     * @return ModuleMessagingConfiguration[]
     */
    public function findAllModuleConfigurations() : array;
}