<?php

namespace Ecotone\Messaging\Handler\Bridge;

use Ecotone\Messaging\Message;

/**
 * Class Bridge
 * @package Ecotone\Messaging\Handler\Bridge
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class Bridge
{
    public function handle(Message $message): Message
    {
        return $message;
    }
}
