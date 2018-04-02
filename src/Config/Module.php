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
     * In here you can register all message handlers, gateways, message channels
     *
     * @param Configuration         $configuration
     * @param ModuleExtension[]     $moduleExtensions
     * @param ConfigurationObserver $configurationObserver
     *
     * @return void
     */
    public function prepare(Configuration $configuration, array $moduleExtensions, ConfigurationObserver $configurationObserver) : void;

//    @TODO change preConfigure for prepare and registerWithin for configure.
// Prepare should register all handlers, later it should stay locked

    /**
     * Runs during configuration phase, when all handlers must be defined
     *
     * @param Configuration $configuration
     * @param ModuleExtension[] $moduleExtensions
     * @param ConfigurationVariableRetrievingService $configurationVariableRetrievingService
     * @param ReferenceSearchService $referenceSearchService
     *
     * @return void
     */
    public function configure(Configuration $configuration, array $moduleExtensions, ConfigurationVariableRetrievingService $configurationVariableRetrievingService, ReferenceSearchService $referenceSearchService): void;

    /**
     * Runs after messaging system was built from configuration
     *
     * @param ConfiguredMessagingSystem $configuredMessagingSystem
     *
     * @return void
     */
    public function postConfigure(ConfiguredMessagingSystem $configuredMessagingSystem): void;
}