<?php

namespace Ecotone\Messaging\Config;

use Ecotone\Messaging\Config\Container\GatewayProxyMethodReference;
use Ecotone\Messaging\Config\Container\GatewayProxyReference;
use Ecotone\Messaging\Endpoint\ExecutionPollingMetadata;
use Ecotone\Messaging\Handler\Gateway\Gateway;
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
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 * @template T
 */
/**
 * licence Apache-2.0
 */
interface ConfiguredMessagingSystem
{
    /**
     * @template T
     * @param class-string<T> $gatewayReferenceName
     * @return T
     * @throws InvalidArgumentException if trying to find not existing gateway reference
     */
    public function getGatewayByName(string $gatewayReferenceName): object;

    /**
     * @return GatewayProxyReference[]
     */
    public function getGatewayList(): array;

    /**
     * @throws InvalidArgumentException if trying to find not existing gateway reference
     */
    public function getNonProxyGatewayByName(GatewayProxyMethodReference $gatewayProxyMethodReference): Gateway;

    public function runConsoleCommand(string $commandName, array $parameters): mixed;

    public function getCommandBus(): CommandBus;

    public function getQueryBus(): QueryBus;

    public function getEventBus(): EventBus;

    public function getDistributedBus(): DistributedBus;

    public function getMessagePublisher(string $referenceName = MessagePublisher::class): MessagePublisher;

    /**
     * @throws InvalidArgumentException if trying to find not existing service reference
     * @template T
     * @param class-string<T> $referenceName
     * @return T
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

    /**
     * Allows to replace configured messaging system with new one
     */
    public function replaceWith(self $messagingSystem): void;
}
