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
 * Interface ConfiguredMessagingSystem
 * @package Ecotone\Messaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ConfiguredMessagingSystem
{
    /**
     * @param string $gatewayReferenceName
     * @throws InvalidArgumentException if trying to find not existing gateway reference
     */
    public function getGatewayByName(string $gatewayReferenceName): object;

    /**
     * @param string $gatewayReferenceName
     * @throws InvalidArgumentException if trying to find not existing gateway reference
     */
    public function getNonProxyGatewayByName(string $gatewayReferenceName): NonProxyCombinedGateway;

    public function runConsoleCommand(string $commandName, array $parameters): mixed;

    /**
     * @return GatewayReference[]
     */
    public function getGatewayList(): iterable;

    public function getCommandBus(): CommandBus;

    public function getQueryBus(): QueryBus;

    public function getEventBus(): EventBus;

    public function getDistributedBus(): DistributedBus;

    public function getMessagePublisher(string $referenceName = MessagePublisher::class): MessagePublisher;

    /**
     * @throws InvalidArgumentException if trying to find not existing service reference
     */
    public function getServiceFromContainer(string $referenceName): object;

    /**
     * @param string $channelName
     * @return MessageChannel
     * @throws ConfigurationException if trying to find not existing channel
     */
    public function getMessageChannelByName(string $channelName): MessageChannel;

    public function run(string $name, ?ExecutionPollingMetadata $executionPollingMetadata = null): void;

    /**
     * @return string[]
     */
    public function list(): array;
}