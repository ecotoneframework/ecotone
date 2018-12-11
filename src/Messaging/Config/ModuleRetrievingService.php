<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Config;

/**
 * Interface ModuleConfigurationRetrievingService
 * @package SimplyCodedSoftware\Messaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ModuleRetrievingService
{
    /**
     * @return Module[]
     */
    public function findAllModuleConfigurations() : array;

    /**
     * @return object[]
     */
    public function findAllExtensionObjects() : array;
}