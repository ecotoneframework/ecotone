<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Config;

/**
 * Interface ModuleConfigurationRetrievingService
 * @package Ecotone\Messaging\Config
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