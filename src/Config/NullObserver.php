<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Config;

/**
 * Class NullObserver
 * @package SimplyCodedSoftware\IntegrationMessaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class NullObserver implements ConfigurationObserver
{
    private function __construct()
    {
    }

    public static function create() : self
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function notifyGatewayBuilderWasRegistered(string $referenceName, string $gatewayType, string $interfaceName): void
    {
        return;
    }

    /**
     * @inheritDoc
     */
    public function notifyMessageChannelWasRegistered(string $messageChannelName, string $channelType): void
    {
        return;
    }

    /**
     * @inheritDoc
     */
    public function notifyConfigurationWasFinished(ConfiguredMessagingSystem $configuredMessagingSystem): void
    {
        return;
    }

    /**
     * @inheritDoc
     */
    public function notifyRequiredAvailableReference(string $referenceName): void
    {
        return;
    }
}