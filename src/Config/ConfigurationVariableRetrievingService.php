<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Config;

use SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException;

/**
 * Interface ConfigurationVariableService
 * @package SimplyCodedSoftware\IntegrationMessaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ConfigurationVariableRetrievingService
{
    /**
     * @param string $variableName
     * @return bool
     */
    public function has(string $variableName) : bool;

    /**
     * @param string $variableName
     * @return mixed everything but not object
     * @throws InvalidArgumentException if variable not found
     */
    public function get(string $variableName);
}