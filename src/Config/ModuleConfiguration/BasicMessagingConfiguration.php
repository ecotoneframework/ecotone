<?php

namespace SimplyCodedSoftware\Messaging\Config\ModuleConfiguration;

use SimplyCodedSoftware\Messaging\Annotation\ModuleConfiguration;
use SimplyCodedSoftware\Messaging\Channel\SimpleMessageChannelBuilder;
use SimplyCodedSoftware\Messaging\Config\Configuration;
use SimplyCodedSoftware\Messaging\Config\ConfiguredMessagingSystem;
use SimplyCodedSoftware\Messaging\Config\ModuleMessagingConfiguration;
use SimplyCodedSoftware\Messaging\Endpoint\EventDrivenMessageHandlerConsumerBuilderFactory;
use SimplyCodedSoftware\Messaging\Endpoint\PollOrThrowMessageHandlerConsumerBuilderFactory;
use SimplyCodedSoftware\Messaging\MessageHeaders;
use SimplyCodedSoftware\Messaging\NullableMessageChannel;

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
        $configuration->registerMessageChannel(SimpleMessageChannelBuilder::createPublishSubscribeChannel(MessageHeaders::ERROR_CHANNEL));
        $configuration->registerMessageChannel(SimpleMessageChannelBuilder::create("nullChannel", NullableMessageChannel::create()));
    }

    /**
     * @inheritDoc
     */
    public function postConfigure(ConfiguredMessagingSystem $configuredMessagingSystem): void
    {
        return;
    }
}