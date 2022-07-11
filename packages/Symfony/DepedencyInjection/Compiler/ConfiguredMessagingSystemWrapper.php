<?php

namespace Ecotone\SymfonyBundle\DepedencyInjection\Compiler;

use Ecotone\Messaging\Config\ConfiguredMessagingSystem;
use Ecotone\Messaging\Endpoint\ExecutionPollingMetadata;
use Ecotone\Messaging\MessageChannel;
use Ecotone\Messaging\MessagePublisher;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\DistributedBus;
use Ecotone\Modelling\EventBus;
use Ecotone\Modelling\QueryBus;
use Ecotone\SymfonyBundle\EcotoneSymfonyBundle;
use Symfony\Component\DependencyInjection\Container;

class ConfiguredMessagingSystemWrapper implements ConfiguredMessagingSystem
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function getGatewayByName(string $gatewayReferenceName): object
    {
        return $this->getConfiguredSystem()->getGatewayByName($gatewayReferenceName);
    }

    public function getNonProxyGatewayByName(string $gatewayReferenceName): \Ecotone\Messaging\Config\NonProxyCombinedGateway
    {
        return $this->getConfiguredSystem()->getNonProxyGatewayByName($gatewayReferenceName);
    }

    public function getGatewayList(): iterable
    {
        return $this->getConfiguredSystem()->getGatewayList();
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
        Assert::isTrue($this->container->has($referenceName), "Service with reference {$referenceName} does not exists");

        return $this->container->get($referenceName);
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

    public function runConsoleCommand(string $commandName, array $parameters): mixed
    {
        return $this->getConfiguredSystem()->runConsoleCommand($commandName, $parameters);
    }

    private function getConfiguredSystem(): ConfiguredMessagingSystem
    {
        return $this->container->get(EcotoneSymfonyBundle::CONFIGURED_MESSAGING_SYSTEM);
    }
}
