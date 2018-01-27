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
     * @param Configuration $configuration
     */
    public function registerWithin(Configuration $configuration) : void;
}