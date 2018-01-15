<?php

namespace SimplyCodedSoftware\Messaging\Config;

use SimplyCodedSoftware\Messaging\Channel\MessageChannelBuilder;
use SimplyCodedSoftware\Messaging\Endpoint\ConsumerFactory;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilder;

/**
 * Class GlobalMessagingSystemConfiguration
 * @package SimplyCodedSoftware\Messaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
final class GlobalMessagingSystemConfiguration implements Configuration
{
    /**
     * @var self
     */
    private static $instance;
    /**
     * @var MessagingSystem
     */
    private static $messagingSystem;

    /**
     * @inheritDoc
     */
    public static function prepare(): MessagingSystemConfiguration
    {
        if (!isset(self::$instance)) {
            self::$instance = MessagingSystemConfiguration::prepare();
        }

        return self::$instance;
    }

    /**
     * @return MessagingSystem
     */
    public static function getMessagingSystem(): MessagingSystem
    {
        return self::$messagingSystem;
    }

    /**
     * @inheritDoc
     */
    public function registerMessageChannel(MessageChannelBuilder $messageChannelBuilder): MessagingSystemConfiguration
    {
        return self::prepare()
            ->registerMessageChannel($messageChannelBuilder);
    }

    /**
     * @inheritDoc
     */
    public function registerMessageHandler(MessageHandlerBuilder $messageHandlerBuilder): MessagingSystemConfiguration
    {
        return self::prepare()
                ->registerMessageHandler($messageHandlerBuilder);
    }

    /**
     * @inheritDoc
     */
    public function registerConsumerFactory(ConsumerFactory $consumerFactory): MessagingSystemConfiguration
    {
        return self::prepare()
                ->registerConsumerFactory($consumerFactory);
    }

    /**
     * @inheritDoc
     */
    public function buildMessagingSystemFromConfiguration(): MessagingSystem
    {
        return self::prepare()
            ->buildMessagingSystemFromConfiguration();
    }
}