<?php

namespace SimplyCodedSoftware\Messaging\Config\ModuleConfiguration;

use SimplyCodedSoftware\Messaging\Config\Configuration;
use SimplyCodedSoftware\Messaging\Config\ConfiguredMessagingSystem;
use SimplyCodedSoftware\Messaging\Config\ModuleConfiguration\Annotation\ModuleConfiguration;
use SimplyCodedSoftware\Messaging\Config\ModuleMessagingConfiguration;
use SimplyCodedSoftware\Messaging\Endpoint\EventDrivenMessageHandlerConsumerBuilderFactory;
use SimplyCodedSoftware\Messaging\Endpoint\PollOrThrowMessageHandlerConsumerBuilderFactory;

/**
 * Class BasicMessagingConfiguration
 * @package SimplyCodedSoftware\Messaging\Config\ModuleConfiguration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ModuleConfiguration()
 */
class BasicMessagingConfiguration implements ModuleMessagingConfiguration
{
    /**
     * @inheritDoc
     */
    public function registerWithin(Configuration $configuration): void
    {
        $configuration->registerConsumerFactory(new EventDrivenMessageHandlerConsumerBuilderFactory());
        $configuration->registerConsumerFactory(new PollOrThrowMessageHandlerConsumerBuilderFactory());
    }

    /**
     * @inheritDoc
     */
    public function postConfigure(ConfiguredMessagingSystem $configuredMessagingSystem): void
    {
        return;
    }
}