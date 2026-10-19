<?php

declare(strict_types=1);

namespace Ecotone\Lite\Test;

use DateTimeImmutable;
use Ecotone\Api\ExtensionObject\ExecutionPollingMetadata;
use Ecotone\Api\Gateway\CommandBus;
use Ecotone\Api\Gateway\DistributedBus;
use Ecotone\Api\Gateway\EcotoneClockInterface;
use Ecotone\Api\Gateway\EventBus;
use Ecotone\Api\Gateway\MessagePublisher;
use Ecotone\Api\Gateway\QueryBus;
use Ecotone\Api\Lite\Test\FlowTestSupport;
use Ecotone\Api\Lite\Test\MessagingTestSupport;
use Ecotone\Api\Messaging\ConfiguredMessagingSystem;
use Ecotone\Api\Messaging\Flow\MessageFlowGraph;
use Ecotone\Api\Messaging\Message;
use Ecotone\Api\Messaging\MessageChannel;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\Api\Scheduling\Duration;
use Ecotone\Messaging\Config\Container\GatewayProxyMethodReference;
use Ecotone\Messaging\Gateway\MessagingEntrypointService;
use Ecotone\Messaging\Handler\Gateway\Gateway;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\AggregateFlow\SaveAggregate\AggregateResolver\AggregateDefinitionRegistry;

/**
 * licence Apache-2.0
 */
final class ConfiguredMessagingSystemWithTestSupport implements ConfiguredMessagingSystem
{
    public function __construct(private ConfiguredMessagingSystem $configuredMessagingSystem, private MessageBuilder $messageBuilder)
    {
    }

    public function getGatewayByName(string $gatewayReferenceName): object
    {
        return $this->configuredMessagingSystem->getGatewayByName($gatewayReferenceName);
    }

    public function getNonProxyGatewayByName(GatewayProxyMethodReference $gatewayProxyMethodReference): Gateway
    {
        return $this->configuredMessagingSystem->getNonProxyGatewayByName($gatewayProxyMethodReference);
    }

    public function runConsoleCommand(string $commandName, array $parameters): mixed
    {
        return $this->configuredMessagingSystem->runConsoleCommand($commandName, $parameters);
    }

    public function getCommandBus(): CommandBus
    {
        return $this->configuredMessagingSystem->getCommandBus();
    }

    public function getQueryBus(): QueryBus
    {
        return $this->configuredMessagingSystem->getQueryBus();
    }

    public function getEventBus(): EventBus
    {
        return $this->configuredMessagingSystem->getEventBus();
    }

    public function getDistributedBus(): DistributedBus
    {
        return $this->configuredMessagingSystem->getDistributedBus();
    }

    public function sendMessage(string $targetChannel, mixed $payload = '', array $metadata = []): mixed
    {
        /** @var MessagingEntrypointService $messagingEntrypoint */
        $messagingEntrypoint = $this->configuredMessagingSystem->getServiceFromContainer(MessagingEntrypointService::class);

        if ($payload instanceof Message) {
            return $messagingEntrypoint->sendMessage(
                $this->messageBuilder->fromMessage($payload)
                    ->setMultipleHeaders($metadata)
                    ->setHeader(MessagingEntrypointService::ENTRYPOINT, $targetChannel)
                    ->build()
            );
        }

        return $messagingEntrypoint->sendWithHeaders($payload, $metadata, $targetChannel, $metadata[MessageHeaders::ROUTING_SLIP] ?? null);
    }

    public function getMessagePublisher(string $referenceName = MessagePublisher::class): MessagePublisher
    {
        return $this->configuredMessagingSystem->getMessagePublisher($referenceName);
    }

    public function getMessagingTestSupport(): MessagingTestSupport
    {
        return $this->getGatewayByName(MessagingTestSupport::class);
    }

    public function getFlowTestSupport(): FlowTestSupport
    {
        return new FlowTestSupport(
            $this->getCommandBus(),
            $this->getEventBus(),
            $this->getQueryBus(),
            $this->getServiceFromContainer(AggregateDefinitionRegistry::class),
            $this->getMessagingTestSupport(),
            $this->getServiceFromContainer(MessagingEntrypointService::class),
            $this->getServiceFromContainer(EcotoneClockInterface::class),
            $this->configuredMessagingSystem
        );
    }

    /**
     * @template T of object
     * @param class-string<T> $referenceName
     * @return T
     */
    public function getServiceFromContainer(string $referenceName): object
    {
        return $this->configuredMessagingSystem->getServiceFromContainer($referenceName);
    }

    public function getMessageChannelByName(string $channelName): MessageChannel
    {
        return $this->configuredMessagingSystem->getMessageChannelByName($channelName);
    }

    public function run(string $name, ?ExecutionPollingMetadata $executionPollingMetadata = null): void
    {
        $this->configuredMessagingSystem->run($name, $executionPollingMetadata);
    }

    public function list(): array
    {
        return $this->configuredMessagingSystem->list();
    }

    public function getGatewayList(): array
    {
        return $this->configuredMessagingSystem->getGatewayList();
    }

    public function getMessageFlowGraph(): MessageFlowGraph
    {
        return $this->configuredMessagingSystem->getMessageFlowGraph();
    }

    public function replaceWith(ConfiguredMessagingSystem $messagingSystem): void
    {
        $this->configuredMessagingSystem->replaceWith($messagingSystem);
    }

    public function changeTime(DateTimeImmutable|Duration $time): self
    {
        if ($time instanceof Duration) {
            $this->getFlowTestSupport()->advanceTimeBy($time);

            return $this;
        }

        $this->getFlowTestSupport()->changeTimeTo($time);

        return $this;
    }
}
