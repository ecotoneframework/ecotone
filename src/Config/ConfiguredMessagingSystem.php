<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Config;

use SimplyCodedSoftware\IntegrationMessaging\MessageChannel;

/**
 * Interface ConfiguredMessagingSystem
 * @package SimplyCodedSoftware\IntegrationMessaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ConfiguredMessagingSystem
{
    /**
     * @param string $gatewayReferenceName
     * @return object
     * @throws \InvalidArgumentException if trying to find not existing gateway reference
     */
    public function getGatewayByName(string $gatewayReferenceName);

    /**
     * @param string $channelName
     * @return MessageChannel
     * @throws ConfigurationException if trying to find not existing channel
     */
    public function getMessageChannelByName(string $channelName) : MessageChannel;

    /**
     * @param string $consumerName
     */
    public function runSeparatelyRunningConsumerBy(string $consumerName) : void;

    /**
     * @return array|string[]
     */
    public function getListOfSeparatelyRunningConsumers() : array;
}