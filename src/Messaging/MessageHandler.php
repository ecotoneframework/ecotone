<?php

declare(strict_types=1);

namespace Ecotone\Messaging;

/**
 * Interface MessageHandler
 * @package Ecotone\Messaging
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface MessageHandler
{
    /**
     * Handles given message
     */
    public function handle(Message $message): void;
}
