<?php

namespace Ecotone\Messaging\Config;

use Ecotone\Messaging\MessageChannel;

/**
 * Interface ConfiguredMessagingSystem
 * @package Ecotone\Messaging\Config
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
     * @param string $gatewayReferenceName
     * @return NonProxyCombinedGateway
     * @throws \InvalidArgumentException if trying to find not existing gateway reference
     */
    public function getNonProxyGatewayByName(string $gatewayReferenceName);

    /**
     * @return GatewayReference[]
     */
    public function getGatewayList() : iterable;

    /**
     * @param string $channelName
     * @return MessageChannel
     * @throws ConfigurationException if trying to find not existing channel
     */
    public function getMessageChannelByName(string $channelName) : MessageChannel;

    /**
     * @param string $endpointId
     */
    public function runSeparatelyRunningEndpointBy(string $endpointId) : void;

    /**
     * @return array|string[]
     */
    public function getListOfSeparatelyRunningConsumers() : array;
}