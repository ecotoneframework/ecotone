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
        $this->publishedEvents[] = $event;
    }

    public function recordCommand(Message $event): void
    {
        $this->sentCommands[] = $event;
    }

    public function recordQuery(Message $event): void
    {
        $this->sentQueries[] = $event;
    }

    public function getRecordedEvents(): array
    {
        return array_map(fn (Message $message) => $message->getPayload(), $this->getRecordedEventMessages());
    }

    public function getRecordedEventMessages(): array
    {
        $events = $this->publishedEvents;
        $this->publishedEvents = [];

        return $events;
    }

    public function getRecordedCommands(): array
    {
        return array_map(fn (Message $message) => $message->getPayload(), $this->getRecordedCommandMessages());
    }

    public function getRecordedCommandMessages(): array
    {
        $commands = $this->sentCommands;
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

        $messages = $this->spiedChannelsMessages[$channelName];
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
