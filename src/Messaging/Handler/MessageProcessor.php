<?php

namespace Ecotone\Messaging\Handler;

use Ecotone\Messaging\Message;

/**
 * @licence Apache-2.0
 */
interface MessageProcessor
{
    public function process(Message $message): ?Message;
}
