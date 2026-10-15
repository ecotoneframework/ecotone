<?php

declare(strict_types=1);

namespace Ecotone\Api\Lite\Test;

use DateTimeInterface;
use Ecotone\Api\Messaging\Message;
use Ecotone\Api\Scheduling\TimeSpan;

/**
 * licence Apache-2.0
 */
interface MessagingTestSupport
{
    /**
     * @return array<int, mixed>
     */
    public function popRecordedEvents(): array;

    /**
     * @return Message[]
     */
    public function popRecordedEventMessages(): array;

    /**
     * @return array<int, mixed>
     */
    /**
     * @return Message[]
     */
    public function popRecordedEventMessagesOfType(string $className): array;

    /**
     * @return Message[]
     */
    public function popRecordedCommandMessagesOfType(string $className): array;

    public function popRecordedCommands(): array;

    /**
     * @return Message[]
     */
    public function popRecordedCommandMessages(): array;

    /**
     * @return array<int, mixed>
     */
    public function popRecordedQueries(): array;

    /**
     * @return Message[]
     */
    public function popRecordedQueryMessages(): array;

    /**
     * @return mixed[]
     */
    public function popRecordedMessagePayloadsFrom(string $channelName): array;

    /**
     * @return Message[]
     */
    public function popRecordedMessagesFrom(string $channelName): array;

    public function discardRecordedMessages(): void;

    public function releaseMessagesAwaitingFor(string $channelName, int|TimeSpan|DateTimeInterface $timeInMillisecondsOrDateTime): void;
}
