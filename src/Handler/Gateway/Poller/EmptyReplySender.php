<?php

namespace SimplyCodedSoftware\Messaging\Handler\Gateway\Poller;

use SimplyCodedSoftware\Messaging\Handler\Gateway\ReplySender;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;

/**
 * Class EmptyPoller
 * @package SimplyCodedSoftware\Messaging\Handler\Gateway\Poller
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