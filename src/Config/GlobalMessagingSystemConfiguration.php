<?php

namespace Messaging\Config;

use Messaging\Channel\MessageChannelBuilder;
use Messaging\Endpoint\PollableConsumerFactory;
use Messaging\Handler\MessageHandlerBuilder;
use Messaging\MessageChannel;

/**
 * Class GlobalMessagingSystemConfiguration
 * @package Messaging\Config
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
    public function setPollableFactory(PollableConsumerFactory $pollableFactory): MessagingSystemConfiguration
    {
        return self::prepare()
                ->setPollableFactory($pollableFactory);
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