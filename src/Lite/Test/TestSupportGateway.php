<?php

declare(strict_types=1);

namespace Ecotone\Lite\Test;

use Ecotone\Messaging\Message;

interface TestSupportGateway
{
    /**
     * @return array<int, mixed>
     */
    public function getPublishedEvents(): array;

    /**
     * Allows to assert metadata of the message
     *
     * @return Message[]
     */
    public function getPublishedEventMessages(): array;

    /**
     * @return array<int, mixed>
     */
    public function getSentCommands(): array;

    /**
     *  Allows to assert metadata of the message
     *
     * @return Message[]
     */
    public function getSentCommandMessages(): array;

    /**
     * @return array<int, mixed>
     */
    public function getSentQueries(): array;

    /**
     *  Allows to assert metadata of the message
     *
     * @return Message[]
     */
    public function getSentQueryMessages(): array;

    public function resetMessages(): void;

    public function releaseMessagesAwaitingFor(string $channelName, int $timeInMilliseconds): void;
}
