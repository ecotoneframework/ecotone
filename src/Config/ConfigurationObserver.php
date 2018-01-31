<?php

namespace SimplyCodedSoftware\Messaging\Config;

/**
 * Interface ConfigurationObserver
 * @package SimplyCodedSoftware\Messaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ConfigurationObserver
{
    /**
     * @param string $referenceName
     * @param string $gatewayType
     * @param string $interfaceName
     */
    public function notifyGatewayBuilderWasRegistered(string $referenceName, string $gatewayType, string $interfaceName) : void;

    /**
     * @param string $messageChannelName
     * @param string $channelType
     * @return void
     */
    public function notifyMessageChannelWasRegistered(string $messageChannelName, string $channelType) : void;

    /**
     * @param ConfiguredMessagingSystem $configuredMessagingSystem
     */
    public function notifyConfigurationWasFinished(ConfiguredMessagingSystem $configuredMessagingSystem) : void;
}