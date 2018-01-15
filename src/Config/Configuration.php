<?php
/**
 * Created by PhpStorm.
 * User: dgafka
 * Date: 01.01.18
 * Time: 21:31
 */

namespace SimplyCodedSoftware\Messaging\Config;

use SimplyCodedSoftware\Messaging\Channel\MessageChannelBuilder;
use SimplyCodedSoftware\Messaging\Endpoint\ConsumerFactory;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilder;


/**
 * Class MessagingSystemConfiguration
 * @package SimplyCodedSoftware\Messaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface Configuration
{
    /**
     * @return MessagingSystemConfiguration
     */
    public static function prepare(): MessagingSystemConfiguration;

    /**
     * @param MessageChannelBuilder $messageChannelBuilder
     * @return MessagingSystemConfiguration
     */
    public function registerMessageChannel(MessageChannelBuilder $messageChannelBuilder): MessagingSystemConfiguration;

    /**
     * @param MessageHandlerBuilder $messageHandlerBuilder
     * @return MessagingSystemConfiguration
     */
    public function registerMessageHandler(MessageHandlerBuilder $messageHandlerBuilder): MessagingSystemConfiguration;

    /**
     * @param ConsumerFactory $consumerFactory
     * @return MessagingSystemConfiguration
     */
    public function registerConsumerFactory(ConsumerFactory $consumerFactory) : MessagingSystemConfiguration;

    /**
     * Initialize messaging system from current configuration.
     * This is one time process, after initialization you won't be able to configure messaging system anymore.
     *
     * @return MessagingSystem
     */
    public function buildMessagingSystemFromConfiguration(): MessagingSystem;
}