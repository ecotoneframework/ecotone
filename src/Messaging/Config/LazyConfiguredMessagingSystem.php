<?php

namespace Ecotone\Messaging\Config;

use Ecotone\Messaging\Endpoint\ExecutionPollingMetadata;
use Ecotone\Messaging\MessageChannel;
use Ecotone\Messaging\MessagePublisher;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\DistributedBus;
use Ecotone\Modelling\EventBus;
use Ecotone\Modelling\QueryBus;
use InvalidArgumentException;

/**
 * configured messaging system is set up on boot, so in case of fetching it during initialization we need to provide lazy config
 */
class LazyConfiguredMessagingSystem implements ConfiguredMessagingSystem
{
    private ?ConfiguredMessagingSystem $configuredMessagingSystem = null;

    public function __construct()
    {
    }

    public function getGatewayByName(string $gatewayReferenceName): object
    {
        return $this->getConfiguredSystem()->getGatewayByName($gatewayReferenceName);
    }

    public function getNonProxyGatewayByName(string $gatewayReferenceName): NonProxyCombinedGateway
    {
        return $this->getConfiguredSystem()->getNonProxyGatewayByName($gatewayReferenceName);
    }

    public function getMessageChannelByName(string $channelName): MessageChannel
    {
        return $this->getConfiguredSystem()->getMessageChannelByName($channelName);
    }

    public function run(string $endpointId, ?ExecutionPollingMetadata $executionPollingMetadata = null): void
    {
        $this->getConfiguredSystem()->run($endpointId, $executionPollingMetadata);
    }

    public function getServiceFromContainer(string $referenceName): object
    {
        return $this->getConfiguredSystem()->getServiceFromContainer($referenceName);
    }

    public function getCommandBus(): CommandBus
    {
        return $this->getGatewayByName(CommandBus::class);
    }

    public function getQueryBus(): QueryBus
    {
        return $this->getGatewayByName(QueryBus::class);
    }

    public function getEventBus(): EventBus
    {
        return $this->getGatewayByName(EventBus::class);
    }

    public function getDistributedBus(): DistributedBus
    {
        return $this->getGatewayByName(DistributedBus::class);
    }

    public function getMessagePublisher(string $referenceName = MessagePublisher::class): MessagePublisher
    {
        return $this->getGatewayByName($referenceName);
    }

    public function list(): array
    {
        return $this->getConfiguredSystem()->list();
    }

    public function getGatewayList(): array
    {
        return $this->getConfiguredSystem()->getGatewayList();
    }

    public function runConsoleCommand(string $commandName, array $parameters): mixed
    {
        return $this->getConfiguredSystem()->runConsoleCommand($commandName, $parameters);
    }

    public function replaceWith(ConfiguredMessagingSystem $messagingSystem): void
    {
        $this->configuredMessagingSystem = $messagingSystem;
    }

    private function getConfiguredSystem(): ConfiguredMessagingSystem
    {
        if (! $this->configuredMessagingSystem) {
            throw new InvalidArgumentException('Configured messaging system was not set');
        }
        return $this->configuredMessagingSystem;
    }
}
