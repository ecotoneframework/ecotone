<?php

declare(strict_types=1);

namespace Ecotone\Lite\Test;

use Ecotone\EventSourcing\EventStore;
use Ecotone\EventSourcing\ProjectionManager;
use Ecotone\Messaging\Config\ConfiguredMessagingSystem;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Endpoint\ExecutionPollingMetadata;
use Ecotone\Messaging\Gateway\MessagingEntrypoint;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageChannel;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\PollableChannel;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Modelling\AggregateMessage;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\Config\BusModule;
use Ecotone\Modelling\Config\ModellingHandlerModule;
use Ecotone\Modelling\Event;
use Ecotone\Modelling\EventBus;
use Ecotone\Modelling\QueryBus;

/**
 * @template T
 */
final class FlowTestSupport
{
    public function __construct(
        private CommandBus $commandBus,
        private EventBus $eventBus,
        private QueryBus $queryBus,
        private MessagingTestSupport $testSupportGateway,
        private MessagingEntrypoint $messagingEntrypoint,
        private ConfiguredMessagingSystem $configuredMessagingSystem
    ) {
    }

    public function sendCommand(object $command, array $metadata = []): self
    {
        $this->commandBus->send($command, $metadata);

        return $this;
    }

    public function sendCommandWithRoutingKey(string $routingKey, mixed $command = [], string $commandMediaType = MediaType::APPLICATION_X_PHP, array $metadata = []): self
    {
        $this->commandBus->sendWithRouting($routingKey, $command, $commandMediaType, $metadata);

        return $this;
    }

    public function publishEvent(object $event, array $metadata = []): self
    {
        $this->eventBus->publish($event, $metadata);

        return $this;
    }

    public function publishEventWithRoutingKey(string $routingKey, mixed $event = [], string $eventMediaType = MediaType::APPLICATION_X_PHP, array $metadata = []): self
    {
        $this->eventBus->publishWithRouting($routingKey, $event, $eventMediaType, $metadata);

        return $this;
    }

    public function sendQuery(object $query, array $metadata = [], ?string $expectedReturnedMediaType = null): mixed
    {
        return $this->queryBus->send($query, $metadata, $expectedReturnedMediaType);
    }

    public function sendQueryWithRouting(string $routingKey, mixed $query = [], string $queryMediaType = MediaType::APPLICATION_X_PHP, array $metadata = [], ?string $expectedReturnedMediaType = null): mixed
    {
        return $this->queryBus->sendWithRouting($routingKey, $query, $queryMediaType, $metadata, $expectedReturnedMediaType);
    }

    public function discardRecordedMessages(): self
    {
        $this->testSupportGateway->discardRecordedMessages();

        return $this;
    }

    public function releaseAwaitingMessagesAndRunConsumer(string $channelName, int $timeInMilliseconds, ?ExecutionPollingMetadata $executionPollingMetadata = null): self
    {
        $this->testSupportGateway->releaseMessagesAwaitingFor($channelName, $timeInMilliseconds);
        $this->run($channelName, $executionPollingMetadata);

        return $this;
    }

    /**
     * @return mixed[]
     */
    public function getSpiedChannelRecordedMessagePayloads(string $channelName): array
    {
        return $this->testSupportGateway->getSpiedChannelRecordedMessagePayloads($channelName);
    }

    /**
     * @return Message[]
     */
    public function getSpiedChannelRecordedMessages(string $channelName): array
    {
        return $this->testSupportGateway->getSpiedChannelRecordedMessages($channelName);
    }

    public function getMessageChannel(string $channelName): MessageChannel|PollableChannel
    {
        return $this->configuredMessagingSystem->getMessageChannelByName($channelName);
    }

    public function run(string $name, ?ExecutionPollingMetadata $executionPollingMetadata = null): self
    {
        $this->configuredMessagingSystem->run($name, $executionPollingMetadata);

        return $this;
    }

    /**
     * @param Event[]|object[]|array[] $streamEvents
     */
    public function withEventStream(string $streamName, array $events): self
    {
        $this->getGateway(EventStore::class)->appendTo($streamName, $events);

        return $this;
    }

    public function deleteEventStream(string $streamName): self
    {
        $gateway = $this->getGateway(EventStore::class);
        if (! $gateway->hasStream($streamName)) {
            return $this;
        }

        $gateway->delete($streamName);

        return $this;
    }

    /**
     * @return Event[]
     */
    public function getEventStreamEvents(string $streamName): array
    {
        return $this->getGateway(EventStore::class)->load($streamName);
    }

    /**
     * @param Event[]|object[]|array[] $events
     */
    public function withEventsFor(string|object|array $identifiers, string $aggregateClass, array $events, int $aggregateVersion = 0): self
    {
        $this->messagingEntrypoint->sendWithHeaders(
            $events,
            [
                AggregateMessage::OVERRIDE_AGGREGATE_IDENTIFIER => is_object($identifiers) ? (string)$identifiers : $identifiers,
                AggregateMessage::TARGET_VERSION => $aggregateVersion,
                AggregateMessage::RESULT_AGGREGATE_OBJECT => $aggregateClass,
                AggregateMessage::RESULT_AGGREGATE_EVENTS => $events,
            ],
            ModellingHandlerModule::getRegisterAggregateSaveRepositoryInputChannel($aggregateClass)
        );

        return $this->discardRecordedMessages();
    }

    public function withStateFor(object $aggregate): self
    {
        $this->messagingEntrypoint->sendWithHeaders(
            $aggregate,
            [
                AggregateMessage::RESULT_AGGREGATE_OBJECT => $aggregate,
            ],
            ModellingHandlerModule::getRegisterAggregateSaveRepositoryInputChannel($aggregate::class)
        );

        return $this;
    }

    public function triggerProjection(string $projectionName): self
    {
        $this->getGateway(ProjectionManager::class)->triggerProjection($projectionName);

        return $this;
    }

    public function initializeProjection(string $projectionName): self
    {
        $this->getGateway(ProjectionManager::class)->initializeProjection($projectionName);

        return $this;
    }

    public function stopProjection(string $projectionName): self
    {
        $this->getGateway(ProjectionManager::class)->stopProjection($projectionName);

        return $this;
    }

    public function resetProjection(string $projectionName): self
    {
        $this->getGateway(ProjectionManager::class)->resetProjection($projectionName);

        return $this;
    }

    public function deleteProjection(string $projectionName): self
    {
        // fixme Calling ProjectionManager to delete the projection throws `Header with name ecotone.eventSourcing.manager.deleteEmittedEvents does not exists` exception
        //$this->getGateway(ProjectionManager::class)->deleteProjection($projectionName);
        $this->configuredMessagingSystem->runConsoleCommand('ecotone:es:delete-projection', ['name' => $projectionName]);

        return $this;
    }

    /**
     * @return mixed[]
     */
    public function getRecordedEvents(): array
    {
        return $this->testSupportGateway->getRecordedEvents();
    }

    /**
     * @return MessageHeaders[]
     */
    public function getRecordedEventHeaders(): array
    {
        return array_map(fn (Message $message) => $message->getHeaders(), $this->testSupportGateway->getRecordedEventMessages());
    }

    /**
     * @return MessageHeaders[]
     */
    public function getRecordedEventRouting(): array
    {
        return array_map(fn (Message $message) => $message->getHeaders()->get('ecotone.modelling.bus.command_by_name'), $this->testSupportGateway->getRecordedEventMessages());
    }

    /**
     * @return mixed[]
     */
    public function getRecordedCommands(): array
    {
        return $this->testSupportGateway->getRecordedCommands();
    }

    /**
     * @return MessageHeaders[]
     */
    public function getRecordedCommandHeaders(): array
    {
        return array_map(fn (Message $message) => $message->getHeaders(), $this->testSupportGateway->getRecordedCommandMessages());
    }

    /**
     * @return string[]
     * @throws MessagingException
     */
    public function getRecordedCommandsWithRouting(): array
    {
        $commandWithRouting = [];
        foreach ($this->getRecordedCommandHeaders() as $commandHeaders) {
            if ($commandHeaders->containsKey(BusModule::COMMAND_CHANNEL_NAME_BY_NAME)) {
                $command = [
                    $commandHeaders->get(BusModule::COMMAND_CHANNEL_NAME_BY_NAME),
                ];

                if ($commandHeaders->containsKey('aggregate.id')) {
                    $command[] = $commandHeaders->get('aggregate.id');
                }

                $commandWithRouting[] = $command;
            }
        }

        return $commandWithRouting;
    }

    /**
     * @template T
     * @param class-string<T> $className
     * @return T
     */
    public function getAggregate(string $className, string|array|object $identifiers): object
    {
        return $this->messagingEntrypoint->sendWithHeaders(
            [],
            [
                AggregateMessage::OVERRIDE_AGGREGATE_IDENTIFIER => is_object($identifiers) ? (string)$identifiers : $identifiers,
            ],
            ModellingHandlerModule::getRegisterAggregateLoadRepositoryInputChannel($className)
        );
    }

    /**
     * @template T
     * @param class-string<T> $className
     * @param string|array $identifiers
     * @return T
     */
    public function getSaga(string $className, string|array $identifiers): object
    {
        return $this->getAggregate($className, $identifiers);
    }

    public function sendDirectToChannel(string $targetChannel, mixed $payload = '', array $metadata = []): mixed
    {
        /** @var MessagingEntrypoint $messagingEntrypoint */
        $messagingEntrypoint = $this->configuredMessagingSystem->getGatewayByName(MessagingEntrypoint::class);

        return $messagingEntrypoint->sendWithHeaders($payload, $metadata, $targetChannel);
    }

    public function sendMessageDirectToChannel(Message $message): mixed
    {
        Assert::isTrue($message->getHeaders()->containsKey(MessagingEntrypoint::ENTRYPOINT), "Message must be sent directly to channel, so it must contains `ecotone.messaging.entrypoint` header. Use `sendDirectToChannel` method instead, if you don't want to add it manually.");
        /** @var MessagingEntrypoint $messagingEntrypoint */
        $messagingEntrypoint = $this->configuredMessagingSystem->getGatewayByName(MessagingEntrypoint::class);

        return $messagingEntrypoint->sendMessage($message);
    }

    /**
     * @param class-string<T> $referenceName
     * @return T
     */
    public function getGateway(string $referenceName): object
    {
        return $this->configuredMessagingSystem->getGatewayByName($referenceName);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function runConsoleCommand(string $name, array $parameters)
    {
        $this->configuredMessagingSystem->runConsoleCommand($name, $parameters);

        return $this;
    }
}
