<?php

namespace Fixture\Configuration;
use PHPUnit\Framework\Assert;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationObserver;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfiguredMessagingSystem;

/**
 * Class DumbConfigurationObserver
 * @package Fixture\Configuration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class DumbConfigurationObserver implements ConfigurationObserver
{
    private $gatewayNotification = false;

    private $messageChannelNotification = false;

    private $configurationNotification = false;

    private $requiredReferences = [];

    public static function create() : self
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function notifyGatewayBuilderWasRegistered(string $referenceName, string $gatewayType, string $interfaceName): void
    {
        $this->gatewayNotification = true;
    }

    /**
     * @inheritDoc
     */
    public function notifyMessageChannelWasRegistered(string $messageChannelName, string $channelType): void
    {
        $this->messageChannelNotification = true;
    }

    /**
     * @inheritDoc
     */
    public function notifyConfigurationWasFinished(ConfiguredMessagingSystem $configuredMessagingSystem): void
    {
        $this->configurationNotification = true;
    }

    public function wasNotifiedCorrectly() : bool
    {
        return $this->gatewayNotification && $this->messageChannelNotification && $this->configurationNotification;
    }

    /**
     * @inheritDoc
     */
    public function notifyRequiredAvailableReference(string $referenceName): void
    {
        $this->requiredReferences[] = $referenceName;
    }

    public function getRequiredReferences() : array
    {
        return $this->requiredReferences;
    }
}