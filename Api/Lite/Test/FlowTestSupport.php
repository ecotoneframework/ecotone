<?php

declare(strict_types=1);

namespace Ecotone\Api\Lite\Test;

use DateTimeImmutable;
use Ecotone\Api\Console\InMemoryConsoleWriter;
use Ecotone\Api\EventSourcing\Event;
use Ecotone\Api\EventSourcing\EventStore;
use Ecotone\Api\ExtensionObject\ExecutionPollingMetadata;
use Ecotone\Api\ExtensionObject\MediaType;
use Ecotone\Api\Gateway\CommandBus;
use Ecotone\Api\Gateway\DistributedBus;
use Ecotone\Api\Gateway\EcotoneClockInterface;
use Ecotone\Api\Gateway\EventBus;
use Ecotone\Api\Gateway\MessagePublisher;
use Ecotone\Api\Gateway\QueryBus;
use Ecotone\Api\Messaging\ConfiguredMessagingSystem;
use Ecotone\Api\Messaging\Message;
use Ecotone\Api\Messaging\MessageChannel;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\Api\Messaging\PollableChannel;
use Ecotone\Api\Scheduling\Duration;
use Ecotone\Api\Scheduling\TimeSpan;
use Ecotone\Lite\Test\DatabaseTablesSetUp;
use Ecotone\Messaging\Gateway\MessagingEntrypointService;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Scheduling\Clock;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\AggregateFlow\SaveAggregate\AggregateResolver\AggregateDefinitionRegistry;
use Ecotone\Modelling\AggregateFlow\SaveAggregate\AggregateResolver\AggregateDefinitionResolver;
use Ecotone\Modelling\AggregateMessage;
use Ecotone\Modelling\Config\AggregateModule;
use Ecotone\Modelling\Config\MessageBusChannel;
use Ecotone\Projecting\ProjectionRegistry;
use Ecotone\Test\StaticPsrClock;
use InvalidArgumentException;

use function sprintf;

use Throwable;

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
        public readonly MessagingTestSupport $testSupportGateway,
        private MessagingEntrypointService $messagingEntrypoint,
        private EcotoneClockInterface $clock,
        private ConfiguredMessagingSystem $configuredMessagingSystem
    ) {
    }

    public function sendCommand(object $command, array $metadata = []): self
    {
        $this->commandBus->send($command, $metadata);

        return $this;
    }

    public function sendCommandWithRouting(string $routingKey, mixed $command = [], string $commandMediaType = MediaType::APPLICATION_X_PHP, array $metadata = []): self
    {
        $this->commandBus->sendWithRouting($routingKey, $command, $commandMediaType, $metadata);

        return $this;
    }

    public function publishEvent(object $event, array $metadata = []): self
    {
        $this->eventBus->publish($event, $metadata);

        return $this;
    }

    public function publishEventWithRouting(string $routingKey, mixed $event = [], string $eventMediaType = MediaType::APPLICATION_X_PHP, array $metadata = []): self
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
     * @return mixed[]
     */
    public function popRecordedMessagePayloadsFrom(string $channelName): array
    {
        return $this->testSupportGateway->popRecordedMessagePayloadsFrom($channelName);
    }

    /**
     * @return Message[]
     */
    public function popRecordedMessagesFrom(string $channelName): array
    {
        return $this->testSupportGateway->popRecordedMessagesFrom($channelName);
    }

    public function getMessageChannel(string $channelName): MessageChannel|PollableChannel
    {
        return $this->configuredMessagingSystem->getMessageChannelByName($channelName);
    }

    public function getMessageChannelByName(string $channelName): MessageChannel|PollableChannel
    {
        return $this->getMessageChannel($channelName);
    }

    public function getMessagePublisher(string $referenceName = MessagePublisher::class): MessagePublisher
    {
        return $this->configuredMessagingSystem->getMessagePublisher($referenceName);
    }

    public function receiveMessageFrom(string $channelName): ?Message
    {
        $messageChannel = $this->getMessageChannel($channelName);
        Assert::isTrue($messageChannel instanceof PollableChannel, "Channel {$channelName} is not pollable");

        return $messageChannel->receive();
    }

    public function run(string $channelOrEndpointName, ?ExecutionPollingMetadata $executionPollingMetadata = null): self
    {
        $this->testSupportGateway->releaseMessagesAwaitingFor($channelOrEndpointName, $this->clock->now());
        $staticClock = $this->staticClock();
        $staticClock?->pinCurrentTime();
        try {
            $this->configuredMessagingSystem->run($channelOrEndpointName, $executionPollingMetadata);
        } finally {
            $staticClock?->returnToPinnedTime();
        }

        return $this;
    }

    /**
     * @param Event[]|object[]|array[] $events
     */
    public function withEventStream(string $streamName, array $events): self
    {
        $this->getGateway(EventStore::class)->appendTo($streamName, $events);

        return $this;
    }

    /**
     * @param Event[]|object[]|array[] $events
     */
    public function withEvents(array $events): self
    {
        return $this->withEventStream(AggregateDefinitionResolver::DEFAULT_STREAM, $events);
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

    public function changeTimeTo(DateTimeImmutable $time): self
    {
        $psrClock = $this->getStaticPsrClockFromContainer();

        if ($psrClock->hasBeenChanged() && $time < $psrClock->now()) {
            throw new InvalidArgumentException(
                sprintf(
                    'Cannot move time backwards: the test clock is at %s and you requested %s. Request a later time, or use advanceTimeBy() to move forward relative to the current time.',
                    $psrClock->now()->format('Y-m-d H:i:s.u'),
                    $time->format('Y-m-d H:i:s.u')
                )
            );
        }

        $psrClock->setCurrentTime($time);

        return $this;
    }

    public function advanceTimeBy(TimeSpan|Duration $span): self
    {
        $duration = $span instanceof TimeSpan ? $span->toDuration() : $span;
        $psrClock = $this->getStaticPsrClockFromContainer();
        $psrClock->setCurrentTime(
            DateTimeImmutable::createFromInterface($psrClock->now())->modify("+{$duration->inMicroseconds()} microseconds")
        );

        return $this;
    }

    private function staticClock(): ?StaticPsrClock
    {
        $clock = $this->clock instanceof Clock ? $this->clock->internalClock() : null;

        return $clock instanceof StaticPsrClock ? $clock : null;
    }

    private function getStaticPsrClockFromContainer(): StaticPsrClock
    {
        try {
            /** @var Clock $clock */
            $clock = $this->configuredMessagingSystem->getServiceFromContainer(EcotoneClockInterface::class);
        } catch (Throwable) {
            throw new InvalidArgumentException(
                'Changing time is only possible when using StaticPsrClock as the ClockInterface. ' .
                'Register ClockInterface::class => new StaticPsrClock() in your container services.'
            );
        }

        if (! $clock instanceof Clock) {
            throw new InvalidArgumentException(
                'Changing time is only possible when using Clock as the EcotoneClockInterface. ' .
                'Register EcotoneClockInterface::class => new Clock(new StaticPsrClock()) in your container services.'
            );
        }

        $clock = $clock->internalClock();
        if (! $clock instanceof StaticPsrClock) {
            throw new InvalidArgumentException(
                'Changing time is only possible when using StaticPsrClock as the ClockInterface. ' .
                'Register ClockInterface::class => new StaticPsrClock() in your container services.'
            );
        }

        return $clock;
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
            AggregateModule::getRegisterAggregateSaveRepositoryInputChannel($aggregateClass, forTesting: true)
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
            AggregateModule::getRegisterAggregateSaveRepositoryInputChannel($aggregate::class, forTesting: true)
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
            $this->getGateway(ProjectionRegistry::class)->get($name)->executeAll();
        }

        return $this;
    }

    public function initializeProjection(string $projectionName): self
    {
        $this->getGateway(ProjectionRegistry::class)->get($projectionName)->init();

        return $this;
    }

    public function resetProjection(string $projectionName): self
    {
        $this->getGateway(ProjectionRegistry::class)->get($projectionName)->executeAllWithReset();

        return $this;
    }

    public function deleteProjection(string $projectionName): self
    {
        $this->getGateway(ProjectionRegistry::class)->get($projectionName)->delete();

        return $this;
    }

    /**
     * This will only be recorded when Message was sent via Event Bus
     *
     * @return mixed[]
     */
    public function popRecordedEvents(): array
    {
        return $this->testSupportGateway->popRecordedEvents();
    }

    /**
     * @template E
     * @param class-string<E> $className
     * @return E[]
     */
    public function popRecordedEventsOfType(string $className): array
    {
        return array_map(fn (Message $message) => $message->getPayload(), $this->testSupportGateway->popRecordedEventMessagesOfType($className));
    }

    /**
     * @template C
     * @param class-string<C> $className
     * @return C[]
     */
    public function popRecordedCommandsOfType(string $className): array
    {
        return array_map(fn (Message $message) => $message->getPayload(), $this->testSupportGateway->popRecordedCommandMessagesOfType($className));
    }

    /**
     * This will only be recorded when Message was sent via Event Bus
     *
     * @return MessageHeaders[]
     */
    public function popRecordedEventHeaders(): array
    {
        return array_map(fn (Message $message) => $message->getHeaders(), $this->testSupportGateway->popRecordedEventMessages());
    }

    /**
     * This will only be recorded when Message was sent via Event Bus
     *
     * @return MessageHeaders[]
     */
    public function popRecordedEventRouting(): array
    {
        return array_map(fn (Message $message) => $message->getHeaders()->get('ecotone.modelling.bus.command_by_name'), $this->testSupportGateway->popRecordedEventMessages());
    }

    /**
     * This will only be recorded when Message was sent via Command Bus
     *
     * @return mixed[]
     */
    public function popRecordedCommands(): array
    {
        return $this->testSupportGateway->popRecordedCommands();
    }

    /**
     * This will only be recorded when Message was sent via Command Bus
     *
     * @return MessageHeaders[]
     */
    public function popRecordedCommandHeaders(): array
    {
        return array_map(fn (Message $message) => $message->getHeaders(), $this->testSupportGateway->popRecordedCommandMessages());
    }

    /**
     * This will only be recorded when Message was sent via Command Bus
     *
     * @return string[]
     */
    public function popRecordedCommandsWithRouting(): array
    {
        $commandWithRouting = [];
        foreach ($this->popRecordedCommandHeaders() as $commandHeaders) {
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
     * @template T of object
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
            AggregateModule::getRegisterAggregateLoadRepositoryInputChannel($className, false)
        );
    }

    /**
     * @template T of object
     * @param class-string<T> $className
     * @return T
     */
    public function getSaga(string $className, string|array $identifiers): object
    {
        return $this->getAggregate($className, $identifiers);
    }

    public function sendDirectToChannel(string $targetChannel, mixed $payload = '', array $metadata = []): mixed
    {
        return $this->messagingEntrypoint->sendWithHeaders($payload, $metadata, $targetChannel, $metadata[MessageHeaders::ROUTING_SLIP] ?? null);
    }

    public function sendDirectToChannelWithMessageReply(string $targetChannel, mixed $payload = '', array $metadata = []): Message
    {
        return $this->messagingEntrypoint->sendWithHeadersWithMessageReply($payload, $metadata, $targetChannel, $metadata[MessageHeaders::ROUTING_SLIP] ?? null);
    }

    public function sendMessageDirectToChannel(string $targetChannel, Message $message): mixed
    {
        Assert::isFalse($message->getHeaders()->containsKey(MessagingEntrypointService::ENTRYPOINT), 'Message must not contain entrypoint header. Make use of first argument in sendDirectToChannel method');

        return $this->messagingEntrypoint->sendMessage(
            MessageBuilder::fromMessage($message)
                ->setHeader(MessagingEntrypointService::ENTRYPOINT, $targetChannel)
                ->build()
        );
    }

    public function sendMessageDirectToChannelWithMessageReply(string $targetChannel, Message $message): Message
    {
        Assert::isFalse($message->getHeaders()->containsKey(MessagingEntrypointService::ENTRYPOINT), 'Message must not contain entrypoint header. Make use of first argument in sendDirectToChannel method');

        return $this->messagingEntrypoint->sendWithHeadersWithMessageReply(
            $message->getPayload(),
            $message->getHeaders()->headers(),
            $targetChannel,
            $message->getHeaders()->containsKey(MessageHeaders::ROUTING_SLIP) ? $message->getHeaders()->get(MessageHeaders::ROUTING_SLIP) : null
        );
    }

    /**
     * @template T of object
     * @param class-string<T> $referenceName
     * @return T
     */
    public function getGateway(string $referenceName): object
    {
        return $this->configuredMessagingSystem->getGatewayByName($referenceName);
    }

    public function getGatewayByName(string $referenceName): object
    {
        return $this->getGateway($referenceName);
    }

    public function getCommandBus(): CommandBus
    {
        return $this->commandBus;
    }

    public function getEventBus(): EventBus
    {
        return $this->eventBus;
    }

    public function getQueryBus(): QueryBus
    {
        return $this->queryBus;
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

    public function setupDatabaseTables(): self
    {
        $this->getServiceFromContainer(DatabaseTablesSetUp::class)->setUpEveryTable();

        return $this;
    }

    public function getInMemoryConsoleWriter(): InMemoryConsoleWriter
    {
        return $this->configuredMessagingSystem->getServiceFromContainer(InMemoryConsoleWriter::class);
    }

    /**
     * @return string[] List of registered consumer endpoint IDs
     */
    public function list(): array
    {
        return $this->configuredMessagingSystem->list();
    }
}
