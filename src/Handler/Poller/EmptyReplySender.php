<?php

namespace Messaging\Handler\Poller;

use Messaging\Handler\ReplySender;
use Messaging\Message;

/**
 * Class EmptyPoller
 * @package Messaging\Handler\Poller
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class EmptyReplySender implements ReplySender
{
    /**
     * @inheritDoc
     */
    public function receiveReply(): void
    {
        return;
    }
}