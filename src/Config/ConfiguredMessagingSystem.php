<?php

namespace SimplyCodedSoftware\Messaging\Config;

use SimplyCodedSoftware\Messaging\MessageChannel;

/**
 * Interface ConfiguredMessagingSystem
 * @package SimplyCodedSoftware\Messaging\Config
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
     * @param string $queueName
     * @return MessageChannel
     * @throws ConfigurationException if trying to find not existing queue
     */
    public function getMessageChannelByName(string $queueName) : MessageChannel;

    /**
     * @param string $consumerName
     */
    public function runSeparatelyRunningConsumerBy(string $consumerName) : void;

    /**
     * @return array|string[]
     */
    public function getListOfSeparatelyRunningConsumers() : array;
}