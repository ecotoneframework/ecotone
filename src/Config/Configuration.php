<?php
/**
 * Created by PhpStorm.
 * User: dgafka
 * Date: 01.01.18
 * Time: 21:31
 */

namespace Messaging\Config;

use Messaging\Endpoint\PollableConsumerFactory;
use Messaging\Handler\MessageHandlerBuilder;
use Messaging\MessageChannel;


/**
 * Class MessagingSystemConfiguration
 * @package Messaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface Configuration
{
    /**
     * @return MessagingSystemConfiguration
     */
    public static function prepare(): MessagingSystemConfiguration;

    /**
     * @param string $messageChannelName
     * @param MessageChannel $messageChannel
     * @return MessagingSystemConfiguration
     */
    public function registerMessageChannel(string $messageChannelName, MessageChannel $messageChannel): MessagingSystemConfiguration;

    /**
     * @param MessageHandlerBuilder $messageHandlerBuilder
     * @return MessagingSystemConfiguration
     */
    public function registerMessageHandler(MessageHandlerBuilder $messageHandlerBuilder): MessagingSystemConfiguration;

    /**
     * @param PollableConsumerFactory $pollableFactory
     * @return MessagingSystemConfiguration
     */
    public function setPollableFactory(PollableConsumerFactory $pollableFactory): MessagingSystemConfiguration;

    /**
     * Initialize messaging system from current configuration.
     * This is one time process, after initialization you won't be able to configure messaging system anymore.
     *
     * @return MessagingSystem
     */
    public function buildMessagingSystemFromConfiguration(): MessagingSystem;
}