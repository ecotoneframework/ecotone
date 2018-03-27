<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Config;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;

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
     * @param ReferenceSearchService $referenceSearchService
     *
     * @return void
     */
    public function registerWithin(Configuration $configuration, array $moduleExtensions, ConfigurationVariableRetrievingService $configurationVariableRetrievingService, ReferenceSearchService $referenceSearchService): void;

    /**
     * Runs after messaging system was built from configuration
     *
     * @param ConfiguredMessagingSystem $configuredMessagingSystem
     *
     * @return void
     */
    public function postConfigure(ConfiguredMessagingSystem $configuredMessagingSystem): void;
}