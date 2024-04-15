<?php

declare(strict_types=1);

namespace Ecotone\Lite\Test;

use Ecotone\Messaging\Message;

interface MessagingTestSupport
{
    /**
     * @return array<int, mixed>
     */
    public function getRecordedEvents(): array;

    /**
     * Allows to assert metadata of the message
     *
     * @return Message[]
     */
    public function getRecordedEventMessages(): array;

    /**
     * @return array<int, mixed>
     */
    public function getRecordedCommands(): array;

    /**
     *  Allows to assert metadata of the message
     *
     * @return Message[]
     */
    public function getRecordedCommandMessages(): array;

    /**
     * @return array<int, mixed>
     */
    public function getRecordedQueries(): array;

    /**
     *  Allows to assert metadata of the message
     *
     * @return Message[]
     */
    public function getRecordedQueryMessages(): array;

    /**
     * @return mixed[]
     */
    public function getRecordedMessagePayloadsFrom(string $channelName): array;

    /**
     * @return Message[]
     */
    public function getRecordedEcotoneMessagesFrom(string $channelName): array;

    public function discardRecordedMessages(): void;

    public function releaseMessagesAwaitingFor(string $channelName, int $timeInMilliseconds): void;
}
