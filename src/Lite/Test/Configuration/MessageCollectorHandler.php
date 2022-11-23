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

    public function getPublishedEvents(): array
    {
        $events = array_map(fn (Message $message) => $message->getPayload(), $this->publishedEvents);
        $this->publishedEvents = [];

        return $events;
    }

    public function getPublishedEventMessages(): array
    {
        $events = $this->publishedEvents;
        $this->publishedEvents = [];

        return $events;
    }

    public function getSentCommands(): array
    {
        $commands = array_map(fn (Message $message) => $message->getPayload(), $this->sentCommands);
        $this->sentCommands = [];

        return $commands;
    }

    public function getSentCommandMessages(): array
    {
        $commands = $this->sentCommands;
        $this->sentCommands = [];

        return $commands;
    }

    public function getSentQueries(): array
    {
        $queries = array_map(fn (Message $message) => $message->getPayload(), $this->sentQueries);
        $this->sentQueries = [];

        return $queries;
    }

    public function getSentQueryMessages(): array
    {
        $queries = $this->sentQueries;
        $this->sentQueries = [];

        return $queries;
    }

    public function resetMessages(): void
    {
        $this->sentQueries = [];
        $this->sentCommands = [];
        $this->publishedEvents = [];
    }
}
