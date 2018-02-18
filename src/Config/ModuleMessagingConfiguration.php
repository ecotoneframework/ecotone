<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Config;

use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;

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
     * @param ReferenceSearchService $referenceSearchService
     */
    public function configure(ReferenceSearchService $referenceSearchService) : void;

    /**
     * Runs after messaging system was built from configuration
     *
     * @param ConfiguredMessagingSystem $configuredMessagingSystem
     *
     * @return void
     */
    public function postConfigure(ConfiguredMessagingSystem $configuredMessagingSystem) : void;
}