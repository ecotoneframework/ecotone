<?php

namespace Fixture\Annotation\FileSystem;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\ModuleConfiguration;
use SimplyCodedSoftware\IntegrationMessaging\Config\Configuration;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationVariableRetrievingService;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfiguredMessagingSystem;
use SimplyCodedSoftware\IntegrationMessaging\Config\ModuleMessagingConfiguration;

/**
 * Class DumbModuleConfiguration
 * @package Fixture\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ModuleConfiguration()
 */
class DumbModuleConfiguration implements ModuleMessagingConfiguration
{
    /**
     * @inheritDoc
     */
    public function registerWithin(Configuration $configuration, ConfigurationVariableRetrievingService $configurationVariableRetrievingService): void
    {

    }

    /**
     * @inheritDoc
     */
    public function postConfigure(ConfiguredMessagingSystem $configuredMessagingSystem): void
    {
        // TODO: Implement postConfigure() method.
    }
}