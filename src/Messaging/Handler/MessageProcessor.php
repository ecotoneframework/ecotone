<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler;

use SimplyCodedSoftware\Messaging\Message;

/**
 * Interface MessageProcessor
 * @package SimplyCodedSoftware\Messaging\Handler
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