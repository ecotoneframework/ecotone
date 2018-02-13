<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\Poller;

use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\ReplySender;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\IntegrationMessaging\Message;
use SimplyCodedSoftware\IntegrationMessaging\PollableChannel;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;

/**
 * Class ReceivePoller
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\Poller
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ChannelReplySender implements ReplySender
{
    /**
     * @var PollableChannel
     */
    private $replyChannel;

    /**
     * @inheritDoc
     */
    public function prepareFor(InterfaceToCall $interfaceToCall, MessageBuilder $messageBuilder): MessageBuilder
    {
        return $messageBuilder
                ->setErrorChannel($this->replyChannel);
    }

    /**
     * ReceivePoller constructor.
     * @param PollableChannel $replyChannel
     */
    public function __construct(PollableChannel $replyChannel)
    {
        $this->replyChannel = $replyChannel;
    }

    /**
     * @inheritDoc
     */
    public function receiveReply(): ?Message
    {
        $message = null;
        while (!$message) {
            $message = $this->replyChannel->receive();
        }

        return $message;
    }

    /**
     * @inheritDoc
     */
    public function hasReply(): bool
    {
        return true;
    }
}