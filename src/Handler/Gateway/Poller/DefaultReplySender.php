<?php

namespace SimplyCodedSoftware\Messaging\Handler\Gateway\Poller;

use SimplyCodedSoftware\Messaging\Channel\QueueChannel;
use SimplyCodedSoftware\Messaging\Handler\Gateway\ReplySender;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessageHeaders;
use SimplyCodedSoftware\Messaging\PollableChannel;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;

/**
 * Class EmptyPoller
 * @package SimplyCodedSoftware\Messaging\Handler\Gateway\Poller
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class DefaultReplySender implements ReplySender
{
    /**
     * @var PollableChannel
     */
    private $defaultReplyChannel;

    /**
     * DefaultReplySender constructor.
     * @param PollableChannel $pollableChannel
     */
    private function __construct(PollableChannel $pollableChannel)
    {
        $this->defaultReplyChannel = $pollableChannel;
    }

    /**
     * @return DefaultReplySender
     */
    public static function create() : self
    {
        return new self(QueueChannel::create());
    }

    /**
     * @inheritDoc
     */
    public function prepareFor(InterfaceToCall $interfaceToCall, MessageBuilder $messageBuilder): MessageBuilder
    {
        if ($interfaceToCall->doesItNotReturnValue()) {
            return $messageBuilder;
        }

        return $messageBuilder
                ->setErrorChannel($this->defaultReplyChannel)
                ->setReplyChannel($this->defaultReplyChannel);
    }

    /**
     * @inheritDoc
     */
    public function receiveReply(): ?Message
    {
        return $this->defaultReplyChannel->receive();
    }

    /**
     * @inheritDoc
     */
    public function hasReply(): bool
    {
        return true;
    }
}