<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Config;

/**
 * Interface ModuleConfigurationExtension
 * @package SimplyCodedSoftware\IntegrationMessaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ModuleConfigurationExtension
{
    /**
     * @param ConfigurationVariableRetrievingService $configurationVariableRetrievingService
     * @return ModuleConfigurationExtension
     */
    public static function create(ConfigurationVariableRetrievingService $configurationVariableRetrievingService) : ModuleConfigurationExtension;
}