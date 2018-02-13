<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Config;

/**
 * Interface ExternalConfiguration
 * @package SimplyCodedSoftware\IntegrationMessaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ModuleMessagingConfiguration
{
    /**
     * Runs during configuration
     *
     * @param Configuration $configuration
     * @param ConfigurationVariableRetrievingService $configurationVariableRetrievingService
     *
     * @return void
     */
    public function registerWithin(Configuration $configuration, ConfigurationVariableRetrievingService $configurationVariableRetrievingService) : void;

    /**
     * Runs after messaging system was built from configuration
     *
     * @param ConfiguredMessagingSystem $configuredMessagingSystem
     *
     * @return void
     */
    public function postConfigure(ConfiguredMessagingSystem $configuredMessagingSystem) : void;
}