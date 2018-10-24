<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Config;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;

/**
 * Interface ExternalConfiguration
 * @package SimplyCodedSoftware\IntegrationMessaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface Module extends ModuleExtension
{
    /**
     * In here you can register all message handlers, gateways, message channels
     *
     * @param Configuration         $configuration
     * @param ModuleExtension[]     $moduleExtensions
     *
     * @return void
     */
    public function prepare(Configuration $configuration, array $moduleExtensions) : void;

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
}