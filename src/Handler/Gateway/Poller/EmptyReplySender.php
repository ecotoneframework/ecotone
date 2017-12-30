<?php

namespace Messaging\Handler\Gateway\Poller;

use Messaging\Handler\Gateway\ReplySender;
use Messaging\Message;
use Messaging\Support\MessageBuilder;

/**
 * Class EmptyPoller
 * @package Messaging\Handler\Gateway\Poller
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class EmptyReplySender implements ReplySender
{
    /**
     * @inheritDoc
     */
    public function addErrorChannel(MessageBuilder $messageBuilder): MessageBuilder
    {
        return $messageBuilder;
    }

    /**
     * @inheritDoc
     */
    public function receiveReply(): ?Message
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function hasReply(): bool
    {
        return false;
    }
}