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
     * Handle given message
     *
     * @param Message $message
     * @throws \Exception
     */
    public function handle(Message $message) : void;
}