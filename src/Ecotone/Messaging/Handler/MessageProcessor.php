<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler;

use Ecotone\Messaging\Message;

/**
 * Interface MessageProcessor
 * @package Ecotone\Messaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface MessageProcessor
{
    /**
     * @param Message $message
     * @return mixed can return everything from null to object, string etc.
     */
    public function processMessage(Message $message);
}