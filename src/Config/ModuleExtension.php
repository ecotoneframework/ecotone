<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Config;

/**
 * Interface ModuleConfigurationExtension
 * @package SimplyCodedSoftware\IntegrationMessaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ModuleExtension
{
    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @return ConfigurationVariable[]
     */
    public function getConfigurationVariables(): array;

    /**
     * Which will be available during build configure phase
     *
     * @return RequiredReference[]
     */
    public function getRequiredReferences(): array;
}