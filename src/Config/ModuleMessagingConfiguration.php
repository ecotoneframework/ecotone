<?php

namespace SimplyCodedSoftware\Messaging\Config;

/**
 * Interface ExternalConfiguration
 * @package SimplyCodedSoftware\Messaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ModuleMessagingConfiguration
{
    /**
     * Runs during configuration
     *
     * @param Configuration $configuration
     */
    public function registerWithin(Configuration $configuration) : void;

    /**
     * Runs after messaging system was built from configuration
     *
     * @param ConfiguredMessagingSystem $configuredMessagingSystem
     */
    public function postConfigure(ConfiguredMessagingSystem $configuredMessagingSystem) : void;
}