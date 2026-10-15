<?php

declare(strict_types=1);

namespace Ecotone\Lite\Test\Configuration;

use Ecotone\Api\Messaging\Message;

/**
 * licence Apache-2.0
 */
final class MessageCollectorHandler
{
    /** @var Message[] */
    private array $publishedEvents = [];
    /** @var Message[] */
    private array $sentCommands = [];
    /** @var Message[] */
    private array $sentQueries = [];
    /** @var Message[] */
    private array $spiedChannelsMessages = [];

    public function recordEvent(Message $event): void
    {
        $this->publishedEvents[$event->getHeaders()->getMessageId()] = $event;
    }

    public function recordCommand(Message $command): void
    {
        $this->sentCommands[] = $command;
    }

    public function recordQuery(Message $query): void
    {
        $this->sentQueries[] = $query;
    }

    public function popRecordedEvents(): array
    {
        return array_map(fn (Message $message) => $message->getPayload(), $this->popRecordedEventMessages());
    }

    public function popRecordedEventMessages(): array
    {
        $events = array_values($this->publishedEvents);
        $this->publishedEvents = [];

        return $events;
    }

    /**
     * @return Message[]
     */
    public function popRecordedEventMessagesOfType(string $className): array
    {
        $popped = array_filter($this->publishedEvents, fn (Message $event) => $event->getPayload() instanceof $className);
        $this->publishedEvents = array_diff_key($this->publishedEvents, $popped);

        return array_values($popped);
    }

    /**
     * @return Message[]
     */
    public function popRecordedCommandMessagesOfType(string $className): array
    {
        $popped = array_filter($this->sentCommands, fn (Message $command) => $command->getPayload() instanceof $className);
        $this->sentCommands = array_values(array_diff_key($this->sentCommands, $popped));

        return array_values($popped);
    }

    public function popRecordedCommands(): array
    {
        return array_map(fn (Message $message) => $message->getPayload(), $this->popRecordedCommandMessages());
    }

    public function popRecordedCommandMessages(): array
    {
        $commands = array_values($this->sentCommands);
        $this->sentCommands = [];

        return $commands;
    }

    public function popRecordedQueries(): array
    {
        return array_map(fn (Message $message) => $message->getPayload(), $this->popRecordedQueryMessages());
    }

    public function popRecordedQueryMessages(): array
    {
        $queries = $this->sentQueries;
        $this->sentQueries = [];

        return $queries;
    }

    public function recordSpiedChannelMessage(string $channelName, Message $message): void
    {
        $this->spiedChannelsMessages[$channelName][] = $message;
    }

    /**
     * @return mixed[]
     */
    public function popRecordedMessagePayloadsFrom(string $channelName): array
    {
        return array_map(fn (Message $message) => $message->getPayload(), $this->popRecordedMessagesFrom($channelName));
    }

    /**
     * @return Message[]
     */
    public function popRecordedMessagesFrom(string $channelName): array
    {
        if (! isset($this->spiedChannelsMessages[$channelName])) {
            return [];
        }

        $messages = array_values($this->spiedChannelsMessages[$channelName]);
        unset($this->spiedChannelsMessages[$channelName]);

        return $messages;
    }

    public function discardRecordedMessages(): void
    {
        $this->sentQueries = [];
        $this->sentCommands = [];
        $this->publishedEvents = [];
        $this->spiedChannelsMessages = [];
    }
}
