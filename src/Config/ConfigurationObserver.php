<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Config;

/**
 * Interface ConfigurationObserver
 * @package SimplyCodedSoftware\IntegrationMessaging\Config
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
     * @param GatewayReference $gatewayReference
     */
    public function notifyGatewayWasBuilt(GatewayReference $gatewayReference) : void;

    /**
     * @param ConfiguredMessagingSystem $configuredMessagingSystem
     */
    public function notifyConfigurationWasFinished(ConfiguredMessagingSystem $configuredMessagingSystem) : void;

    /**
     * @param string $referenceName
     */
    public function notifyRequiredAvailableReference(string $referenceName) : void;
}