<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Config;

/**
 * Interface ModuleConfigurationRetrievingService
 * @package SimplyCodedSoftware\IntegrationMessaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ModuleRetrievingService
{
    /**
     * @return Module[]
     */
    public function findAllModuleConfigurations() : array;

    /**
     * @return ModuleExtension[]
     */
    public function findAllModuleExtensionConfigurations() : array;
}