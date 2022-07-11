<?php

namespace Ecotone\Dbal\Recoverability;

use Ecotone\Messaging\Handler\Recoverability\ErrorContext;
use Ecotone\Messaging\Message;

interface DeadLetterGateway
{
    /**
     * @return ErrorContext[]
     */
    public function list(int $limit, int $offset): array;

    public function show(string $messageId): Message;

    public function reply(string $messageId): void;

    public function replyAll(): void;

    public function delete(string $messageId): void;
}
