<?php

declare(strict_types=1);

namespace Ecotone\Lite\Test;

use Ecotone\Messaging\Config\ConfiguredMessagingSystem;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Endpoint\ExecutionPollingMetadata;
use Ecotone\Messaging\Gateway\MessagingEntrypoint;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\MessagingException;
use Ecotone\Modelling\AggregateMessage;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\Config\BusModule;
use Ecotone\Modelling\Config\ModellingHandlerModule;
use Ecotone\Modelling\EventBus;
use Ecotone\Modelling\QueryBus;

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

    public function publishEvent(object $event): self
    {
        $this->eventBus->publish($event);

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

    public function run(string $name, ?ExecutionPollingMetadata $executionPollingMetadata = null): self
    {
        $this->configuredMessagingSystem->run($name, $executionPollingMetadata);

        return $this;
    }

    public function sendQueryWithRouting(string $routingKey, mixed $query = [], string $queryMediaType = MediaType::APPLICATION_X_PHP, array $metadata = [], ?string $expectedReturnedMediaType = null): mixed
    {
        return $this->queryBus->sendWithRouting($routingKey, $query, $queryMediaType, $metadata, $expectedReturnedMediaType);
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

                if ($commandHeaders->containsKey("aggregate.id")) {
                    $command[] = $commandHeaders->get("aggregate.id");
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
}
