<?php

namespace Ecotone\Messaging\Config;

use function array_keys;

use Ecotone\Messaging\Config\Container\ChannelReference;
use Ecotone\Messaging\Config\Container\ConsoleCommandReference;
use Ecotone\Messaging\Config\Container\EndpointRunnerReference;
use Ecotone\Messaging\Config\Container\GatewayProxyMethodReference;
use Ecotone\Messaging\Endpoint\EndpointRunner;
use Ecotone\Messaging\Endpoint\ExecutionPollingMetadata;
use Ecotone\Messaging\Handler\Gateway\Gateway;
use Ecotone\Messaging\MessageChannel;
use Ecotone\Messaging\MessagePublisher;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\DistributedBus;
use Ecotone\Modelling\EventBus;
use Ecotone\Modelling\QueryBus;
use Psr\Container\ContainerInterface;

/**
 * @TODO wrap with nice exceptions on not found
 */
/**
 * licence Apache-2.0
 */
class MessagingSystemContainer implements ConfiguredMessagingSystem
{
    /**
     * @param array<string, string> $pollingEndpoints a map of endpointId => consumer lifecycle runner reference id
     */
    public function __construct(private ContainerInterface $container, private array $pollingEndpoints, private array $gatewayList)
    {
    }

    public function getGatewayByName(string $gatewayReferenceName): object
    {
        return $this->container->get($gatewayReferenceName);
    }

    public function getNonProxyGatewayByName(GatewayProxyMethodReference $gatewayProxyMethodReference): Gateway
    {
        return $this->container->get($gatewayProxyMethodReference);
    }

    public function runConsoleCommand(string $commandName, array $parameters): mixed
    {
        $consoleCommandReference = new ConsoleCommandReference($commandName);
        if (! $this->container->has($consoleCommandReference)) {
            throw InvalidArgumentException::create("Trying to run not existing console command {$commandName}");
        }
        /** @var ConsoleCommandRunner $commandRunner */
        $commandRunner = $this->container->get($consoleCommandReference);
        return $commandRunner->run($parameters);
    }

    public function getCommandBus(): CommandBus
    {
        return $this->container->get(CommandBus::class);
    }

    public function getQueryBus(): QueryBus
    {
        return $this->container->get(QueryBus::class);
    }

    public function getEventBus(): EventBus
    {
        return $this->container->get(EventBus::class);
    }

    public function getDistributedBus(): DistributedBus
    {
        return $this->container->get(DistributedBus::class);
    }

    public function getMessagePublisher(string $referenceName = MessagePublisher::class): MessagePublisher
    {
        return $this->container->get($referenceName);
    }

    public function getServiceFromContainer(string $referenceName): object
    {
        return $this->container->get($referenceName);
    }

    public function getMessageChannelByName(string $channelName): MessageChannel
    {
        return $this->container->get(new ChannelReference($channelName));
    }

    public function run(string $endpointId, ?ExecutionPollingMetadata $executionPollingMetadata = null): void
    {
        if (! isset($this->pollingEndpoints[$endpointId])) {
            throw InvalidArgumentException::create("Endpoint with id {$endpointId} was not found");
        }
        /** @var EndpointRunner $endpointRunner */
        $endpointRunner = $this->container->get(new EndpointRunnerReference($endpointId));
        $endpointRunner->runEndpointWithExecutionPollingMetadata($executionPollingMetadata);
    }

    public function list(): array
    {
        return array_keys($this->pollingEndpoints);
    }

    public function replaceWith(ConfiguredMessagingSystem $messagingSystem): void
    {
        Assert::isTrue($messagingSystem instanceof MessagingSystemContainer, 'Can only replace with ' . self::class);

        $this->container = $messagingSystem->container;
        $this->pollingEndpoints = $messagingSystem->pollingEndpoints;
    }

    public function getGatewayList(): array
    {
        return $this->gatewayList;
    }
}
