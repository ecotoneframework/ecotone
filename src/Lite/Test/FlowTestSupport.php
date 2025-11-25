<?php

declare(strict_types=1);

namespace Ecotone\Lite\Test;

use DateTimeInterface;
use Ecotone\EventSourcing\EventStore;
use Ecotone\EventSourcing\ProjectionManager;
use Ecotone\Messaging\Config\ConfiguredMessagingSystem;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Endpoint\ExecutionPollingMetadata;
use Ecotone\Messaging\Gateway\MessagingEntrypoint;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageChannel;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\PollableChannel;
use Ecotone\Messaging\Scheduling\TimeSpan;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\AggregateFlow\SaveAggregate\AggregateResolver\AggregateDefinitionRegistry;
use Ecotone\Modelling\AggregateMessage;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\Config\AggregrateModule;
use Ecotone\Modelling\Config\MessageBusChannel;
use Ecotone\Modelling\DistributedBus;
use Ecotone\Modelling\Event;
use Ecotone\Modelling\EventBus;
use Ecotone\Modelling\QueryBus;
use Ecotone\Projecting\ProjectionRegistry;

/**
 * @template T
 */
/**
 * licence Apache-2.0
 */
final class FlowTestSupport
{
    public function __construct(
        private CommandBus $commandBus,
        private EventBus $eventBus,
        private QueryBus $queryBus,
        private AggregateDefinitionRegistry $aggregateDefinitionRegistry,
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

    /**
     * @param int $time Time in milliseconds or TimeSpan object
     *
     * @deprecated use run instead
     */
    public function releaseAwaitingMessagesAndRunConsumer(string $channelName, int|TimeSpan|DateTimeInterface $time, ?ExecutionPollingMetadata $executionPollingMetadata = null): self
    {
        $this->run($channelName, $executionPollingMetadata, is_int($time) ? TimeSpan::withMilliseconds($time) : $time);

        return $this;
    }

    /**
     * @return mixed[]
     */
    public function getRecordedMessagePayloadsFrom(string $channelName): array
    {
        return $this->testSupportGateway->getRecordedMessagePayloadsFrom($channelName);
    }

    /**
     * @return Message[]
     */
    public function getRecordedEcotoneMessagesFrom(string $channelName): array
    {
        return $this->testSupportGateway->getRecordedEcotoneMessagesFrom($channelName);
    }

    public function getMessageChannel(string $channelName): MessageChannel|PollableChannel
    {
        return $this->configuredMessagingSystem->getMessageChannelByName($channelName);
    }

    public function receiveMessageFrom(string $channelName): ?Message
    {
        $messageChannel = $this->getMessageChannel($channelName);
        Assert::isTrue($messageChannel instanceof PollableChannel, "Channel {$channelName} is not pollable");

        return $messageChannel->receive();
    }

    /**
     * @param int|TimeSpan|DateTimeInterface $releaseAwaitingFor will release messages which are delayed for given time
     */
    public function run(string $name, ?ExecutionPollingMetadata $executionPollingMetadata = null, TimeSpan|DateTimeInterface|null $releaseAwaitingFor = null): self
    {
        if ($releaseAwaitingFor) {
            $this->testSupportGateway->releaseMessagesAwaitingFor($name, $releaseAwaitingFor);
        }
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

    /**
     * Append events to the default event stream for testing.
     * This is a convenience method for tests that don't need to specify a stream name.
     *
     * @param Event[]|object[]|array[] $events
     */
    public function withEvents(array $events): self
    {
        return $this->withEventStream('default', $events);
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
        $aggregateDefinition = $this->aggregateDefinitionRegistry->getFor(Type::object($aggregateClass));
        Assert::isTrue($aggregateDefinition->isEventSourced(), "Aggregate {$aggregateClass} is not event sourced. Can't store events for it.");

        $this->messagingEntrypoint->sendWithHeaders(
            [],
            [
                AggregateMessage::OVERRIDE_AGGREGATE_IDENTIFIER => is_object($identifiers) ? (string)$identifiers : $identifiers,
                AggregateMessage::TEST_SETUP_AGGREGATE_VERSION => $aggregateVersion,
                AggregateMessage::TEST_SETUP_AGGREGATE_CLASS => $aggregateClass,
                AggregateMessage::TEST_SETUP_AGGREGATE_INSTANCE => new $aggregateClass(),
                AggregateMessage::TEST_SETUP_AGGREGATE_EVENTS => $events,
            ],
            AggregrateModule::getRegisterAggregateSaveRepositoryInputChannel($aggregateClass, forTesting: true)
        );

        return $this;
    }

    public function withStateFor(object $aggregate): self
    {
        $this->messagingEntrypoint->sendWithHeaders(
            $aggregate,
            [
                AggregateMessage::TEST_SETUP_AGGREGATE_INSTANCE => $aggregate,
                AggregateMessage::TEST_SETUP_AGGREGATE_CLASS => $aggregate::class,
            ],
            AggregrateModule::getRegisterAggregateSaveRepositoryInputChannel($aggregate::class, forTesting: true)
        );

        return $this;
    }

    public function triggerProjection(string|array $projectionName): self
    {
        if (is_string($projectionName)) {
            $projectionName = [$projectionName];
        }

        Assert::allStrings($projectionName, '$projectionName must be single or collection of strings');

        foreach ($projectionName as $name) {
            if ($this->getGateway(ProjectionRegistry::class)->has($name)) {
                $this->getGateway(ProjectionRegistry::class)->get($name)->backfill();
            } else {
                $this->getGateway(ProjectionManager::class)->triggerProjection($name);
            }
        }

        return $this;
    }

    public function initializeProjection(string $projectionName, array $metadata = []): self
    {
        $projectionRegistry = $this->getGateway(ProjectionRegistry::class);
        if ($projectionRegistry->has($projectionName)) {
            $projectionRegistry->get($projectionName)->init();
        } else {
            $this->getGateway(ProjectionManager::class)->initializeProjection($projectionName, $metadata);
        }

        return $this;
    }

    public function stopProjection(string $projectionName): self
    {
        if ($this->getGateway(ProjectionRegistry::class)->has($projectionName)) {
            // Not handled in ProjectionRegistry
            return $this;
        } else {
            $this->getGateway(ProjectionManager::class)->stopProjection($projectionName);
        }

        return $this;
    }

    public function resetProjection(string $projectionName): self
    {
        if ($this->getGateway(ProjectionRegistry::class)->has($projectionName)) {
            $projectionManager = $this->getGateway(ProjectionRegistry::class)->get($projectionName);
            $projectionManager->delete();
            $projectionManager->init();
        } else {
            $this->getGateway(ProjectionManager::class)->resetProjection($projectionName);
        }


        return $this;
    }

    public function deleteProjection(string $projectionName): self
    {
        if ($this->getGateway(ProjectionRegistry::class)->has($projectionName)) {
            $this->getGateway(ProjectionRegistry::class)->get($projectionName)->delete();
        } else {
            // fixme Calling ProjectionManager to delete the projection throws `Header with name ecotone.eventSourcing.manager.deleteEmittedEvents does not exists` exception
            //$this->getGateway(ProjectionManager::class)->deleteProjection($projectionName);
            $this->configuredMessagingSystem->runConsoleCommand('ecotone:es:delete-projection', ['name' => $projectionName]);
        }

        return $this;
    }

    /**
     * This will only be recorded when Message was sent via Event Bus
     *
     * @return mixed[]
     */
    public function getRecordedEvents(): array
    {
        return $this->testSupportGateway->getRecordedEvents();
    }

    /**
     * This will only be recorded when Message was sent via Event Bus
     *
     * @return MessageHeaders[]
     */
    public function getRecordedEventHeaders(): array
    {
        return array_map(fn (Message $message) => $message->getHeaders(), $this->testSupportGateway->getRecordedEventMessages());
    }

    /**
     * This will only be recorded when Message was sent via Event Bus
     *
     * @return MessageHeaders[]
     */
    public function getRecordedEventRouting(): array
    {
        return array_map(fn (Message $message) => $message->getHeaders()->get('ecotone.modelling.bus.command_by_name'), $this->testSupportGateway->getRecordedEventMessages());
    }

    /**
     * This will only be recorded when Message was sent via Command Bus
     *
     * @return mixed[]
     */
    public function getRecordedCommands(): array
    {
        return $this->testSupportGateway->getRecordedCommands();
    }

    /**
     * This will only be recorded when Message was sent via Command Bus
     *
     * @return MessageHeaders[]
     */
    public function getRecordedCommandHeaders(): array
    {
        return array_map(fn (Message $message) => $message->getHeaders(), $this->testSupportGateway->getRecordedCommandMessages());
    }

    /**
     * This will only be recorded when Message was sent via Command Bus
     *
     * @return string[]
     * @throws MessagingException
     */
    public function getRecordedCommandsWithRouting(): array
    {
        $commandWithRouting = [];
        foreach ($this->getRecordedCommandHeaders() as $commandHeaders) {
            if ($commandHeaders->containsKey(MessageBusChannel::COMMAND_CHANNEL_NAME_BY_NAME)) {
                $command = [
                    $commandHeaders->get(MessageBusChannel::COMMAND_CHANNEL_NAME_BY_NAME),
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
    public function getAggregate(string $className, string|int|array|object $identifiers): object
    {
        return $this->messagingEntrypoint->sendWithHeaders(
            [],
            [
                AggregateMessage::OVERRIDE_AGGREGATE_IDENTIFIER => is_object($identifiers) ? (string)$identifiers : $identifiers,
            ],
            AggregrateModule::getRegisterAggregateLoadRepositoryInputChannel($className, false)
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

        return $messagingEntrypoint->sendWithHeaders($payload, $metadata, $targetChannel, $metadata[MessageHeaders::ROUTING_SLIP] ?? null);
    }

    public function sendDirectToChannelWithMessageReply(string $targetChannel, mixed $payload = '', array $metadata = []): Message
    {
        /** @var MessagingEntrypoint $messagingEntrypoint */
        $messagingEntrypoint = $this->configuredMessagingSystem->getGatewayByName(MessagingEntrypoint::class);

        return $messagingEntrypoint->sendWithHeadersWithMessageReply($payload, $metadata, $targetChannel, $metadata[MessageHeaders::ROUTING_SLIP] ?? null);
    }

    public function sendMessageDirectToChannel(string $targetChannel, Message $message): mixed
    {
        Assert::isFalse($message->getHeaders()->containsKey(MessagingEntrypoint::ENTRYPOINT), 'Message must not contain entrypoint header. Make use of first argument in sendDirectToChannel method');
        /** @var MessagingEntrypoint $messagingEntrypoint */
        $messagingEntrypoint = $this->configuredMessagingSystem->getGatewayByName(MessagingEntrypoint::class);

        return $messagingEntrypoint->sendMessage(
            MessageBuilder::fromMessage($message)
                ->setHeader(MessagingEntrypoint::ENTRYPOINT, $targetChannel)
                ->build()
        );
    }

    public function sendMessageDirectToChannelWithMessageReply(string $targetChannel, Message $message): Message
    {
        Assert::isFalse($message->getHeaders()->containsKey(MessagingEntrypoint::ENTRYPOINT), 'Message must not contain entrypoint header. Make use of first argument in sendDirectToChannel method');
        /** @var MessagingEntrypoint $messagingEntrypoint */
        $messagingEntrypoint = $this->configuredMessagingSystem->getGatewayByName(MessagingEntrypoint::class);

        return $messagingEntrypoint->sendWithHeadersWithMessageReply(
            $message->getPayload(),
            $message->getHeaders()->headers(),
            $targetChannel,
            $message->getHeaders()->containsKey(MessageHeaders::ROUTING_SLIP) ? $message->getHeaders()->get(MessageHeaders::ROUTING_SLIP) : null
        );
    }

    /**
     * @template T
     * @param class-string<T> $referenceName
     * @return T
     */
    public function getGateway(string $referenceName): object
    {
        return $this->configuredMessagingSystem->getGatewayByName($referenceName);
    }

    public function getDistributedBus(string $referenceName = DistributedBus::class): DistributedBus
    {
        return $this->getGateway($referenceName);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function runConsoleCommand(string $name, array $parameters = [])
    {
        $this->configuredMessagingSystem->runConsoleCommand($name, $parameters);

        return $this;
    }

    public function getServiceFromContainer(string $serviceName): object
    {
        return $this->configuredMessagingSystem->getServiceFromContainer($serviceName);
    }

    /**
     * @return string[] List of registered consumer endpoint IDs
     */
    public function list(): array
    {
        return $this->configuredMessagingSystem->list();
    }
}
