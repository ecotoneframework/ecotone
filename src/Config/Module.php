<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Config;

/**
 * Interface ExternalConfiguration
 * @package SimplyCodedSoftware\IntegrationMessaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface Module
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

    /**
     * Runs on messaging configuration startup
     *
     * @param Configuration $configuration
     * @param ModuleExtension[] $moduleExtensions
     * @param ConfigurationVariableRetrievingService $configurationVariableRetrievingService
     *
     * @return void
     */
    public function registerWithin(Configuration $configuration, array $moduleExtensions, ConfigurationVariableRetrievingService $configurationVariableRetrievingService): void;

    /**
     * Runs after messaging system was built from configuration
     *
     * @param ConfiguredMessagingSystem $configuredMessagingSystem
     *
     * @return void
     */
    public function postConfigure(ConfiguredMessagingSystem $configuredMessagingSystem): void;
}