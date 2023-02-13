<?php

declare(strict_types=1);

namespace Ecotone\Lite\Test\Configuration;

use Ecotone\Messaging\Message;

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

    public function getRecordedEvents(): array
    {
        return array_map(fn (Message $message) => $message->getPayload(), $this->getRecordedEventMessages());
    }

    public function getRecordedEventMessages(): array
    {
        $events = array_values($this->publishedEvents);
        $this->publishedEvents = [];

        return $events;
    }

    public function getRecordedCommands(): array
    {
        return array_map(fn (Message $message) => $message->getPayload(), $this->getRecordedCommandMessages());
    }

    public function getRecordedCommandMessages(): array
    {
        $commands = array_values($this->sentCommands);
        $this->sentCommands = [];

        return $commands;
    }

    public function getRecordedQueries(): array
    {
        return array_map(fn (Message $message) => $message->getPayload(), $this->getRecordedQueryMessages());
    }

    public function getRecordedQueryMessages(): array
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
    public function getSpiedChannelRecordedMessagePayloads(string $channelName): array
    {
        return array_map(fn (Message $message) => $message->getPayload(), $this->getSpiedChannelRecordedMessages($channelName));
    }

    /**
     * @return Message[]
     */
    public function getSpiedChannelRecordedMessages(string $channelName): array
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
