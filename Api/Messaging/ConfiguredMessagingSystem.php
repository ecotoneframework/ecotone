<?php

namespace Ecotone\Api\Messaging;

use Ecotone\Api\ExtensionObject\ExecutionPollingMetadata;
use Ecotone\Api\Gateway\CommandBus;
use Ecotone\Api\Gateway\DistributedBus;
use Ecotone\Api\Gateway\EventBus;
use Ecotone\Api\Gateway\MessagePublisher;
use Ecotone\Api\Gateway\QueryBus;
use Ecotone\Api\Messaging\Flow\MessageFlowGraph;
use Ecotone\Messaging\Config\Container\GatewayProxyMethodReference;
use Ecotone\Messaging\Config\Container\GatewayProxyReference;
use Ecotone\Messaging\Handler\Gateway\Gateway;

/**
 * @template T
 */
/**
 * licence Apache-2.0
 */
interface ConfiguredMessagingSystem
{
    /**
     * @template T of object
     * @param class-string<T> $gatewayReferenceName
     * @return T
     */
    public function getGatewayByName(string $gatewayReferenceName): object;

    /**
     * @return GatewayProxyReference[]
     */
    public function getGatewayList(): array;

    public function getMessageFlowGraph(): MessageFlowGraph;

    public function getNonProxyGatewayByName(GatewayProxyMethodReference $gatewayProxyMethodReference): Gateway;

    public function runConsoleCommand(string $commandName, array $parameters): mixed;

    public function getCommandBus(): CommandBus;

    public function getQueryBus(): QueryBus;

    public function getEventBus(): EventBus;

    public function getDistributedBus(): DistributedBus;

    public function getMessagePublisher(string $referenceName = MessagePublisher::class): MessagePublisher;

    /**
     * @template T of object
     * @param class-string<T> $referenceName
     * @return T
     */
    public function getServiceFromContainer(string $referenceName): object;

    public function getMessageChannelByName(string $channelName): MessageChannel;

    public function run(string $name, ?ExecutionPollingMetadata $executionPollingMetadata = null): void;

    /**
     * @return string[]
     */
    public function list(): array;

    public function replaceWith(self $messagingSystem): void;
}
