<?php

declare(strict_types=1);

namespace Ecotone\Messaging;

/**
 * Interface MessageChannel
 * @package Ecotone\Messaging
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
interface MessageChannel
{
    /**
     * Send message to this channel
     */
    public function send(Message $message): void;
}
