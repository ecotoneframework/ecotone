<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\Poller;

use SimplyCodedSoftware\IntegrationMessaging\Channel\QueueChannel;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\ReplySender;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\IntegrationMessaging\Message;
use SimplyCodedSoftware\IntegrationMessaging\MessageHeaders;
use SimplyCodedSoftware\IntegrationMessaging\PollableChannel;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;

/**
 * Class EmptyPoller
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\Poller
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