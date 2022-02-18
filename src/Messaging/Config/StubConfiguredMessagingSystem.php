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

class StubConfiguredMessagingSystem implements ConfiguredMessagingSystem
{
    public function getGatewayByName(string $gatewayReferenceName): object
    {
        throw new \InvalidArgumentException("Calling stub messaging system");
    }

    public function getNonProxyGatewayByName(string $gatewayReferenceName): \Ecotone\Messaging\Config\NonProxyCombinedGateway
    {
        throw new \InvalidArgumentException("Calling stub messaging system");
    }

    public function getGatewayList(): iterable
    {
        throw new \InvalidArgumentException("Calling stub messaging system");
    }

    public function getCommandBus(): CommandBus
    {
        throw new \InvalidArgumentException("Calling stub messaging system");
    }

    public function getQueryBus(): QueryBus
    {
        throw new \InvalidArgumentException("Calling stub messaging system");
    }

    public function getEventBus(): EventBus
    {
        throw new \InvalidArgumentException("Calling stub messaging system");
    }

    public function getDistributedBus(): DistributedBus
    {
        throw new \InvalidArgumentException("Calling stub messaging system");
    }

    public function getMessagePublisher(string $referenceName = MessagePublisher::class): MessagePublisher
    {
        throw new \InvalidArgumentException("Calling stub messaging system");
    }

    public function getMessageChannelByName(string $channelName): MessageChannel
    {
        throw new \InvalidArgumentException("Calling stub messaging system");
    }

    public function run(string $endpointId, ?ExecutionPollingMetadata $executionPollingMetadata = null): void
    {
        throw new \InvalidArgumentException("Calling stub messaging system");
    }

    public function getServiceFromContainer(string $referenceName): object
    {
        throw new \InvalidArgumentException("Calling stub messaging system");
    }

    public function list(): array
    {
        throw new \InvalidArgumentException("Calling stub messaging system");
    }

    public function runConsoleCommand(string $commandName, array $parameters): mixed
    {
        return null;
    }
}