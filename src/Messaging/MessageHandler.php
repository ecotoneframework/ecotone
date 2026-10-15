<?php

declare(strict_types=1);

namespace Ecotone\Messaging;

use Ecotone\Api\Messaging\Message;
use Stringable;

/**
 * Interface MessageHandler
 * @package Ecotone\Messaging
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
interface MessageHandler extends Stringable
{
    /**
     * Handles given message
     */
    public function handle(Message $message): void;
}
