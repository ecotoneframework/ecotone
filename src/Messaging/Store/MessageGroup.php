<?php

namespace Ecotone\Messaging\Store;

use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessagingException;

/**
 * Interface MessageGroup - used in multiple messages scenarios
 * @package Ecotone\Messaging\Store
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
interface MessageGroup
{
    /**
     * @param Message $message
     * @throws MessagingException
     */
    public function add(Message $message): void;

    /**
     * @param Message $message
     */
    public function remove(Message $message): void;

    /**
     * @param Message $message
     * @return bool
     */
    public function canBeAdded(Message $message): bool;

    /**
     * @return array|Message[]
     */
    public function messages(): array;

    /**
     * @return string
     */
    public function groupId(): string;

    /**
     * @return bool
     */
    public function isEmpty(): bool;

    /**
     * @return int
     */
    public function size(): int;

    /**
     * Clears group from messages
     */
    public function clear(): void;
}
