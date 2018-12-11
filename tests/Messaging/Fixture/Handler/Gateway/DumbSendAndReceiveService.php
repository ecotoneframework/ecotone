<?php

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Handler\Gateway;

use SimplyCodedSoftware\Messaging\Channel\DirectChannel;
use SimplyCodedSoftware\Messaging\Handler\Gateway\CustomSendAndReceiveService;
use SimplyCodedSoftware\Messaging\Handler\Gateway\SendAndReceiveService;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessageChannel;
use SimplyCodedSoftware\Messaging\PollableChannel;
use SimplyCodedSoftware\Messaging\SubscribableChannel;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;

/**
 * Class DumbReplyReceiver
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Handler\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class DumbSendAndReceiveService implements CustomSendAndReceiveService
{
    /**
     * @var MessageBuilder
     */
    private $messageToSend;
    /**
     * @var Message
     */
    private $messageToReceive;

    public static function create() : self
    {
        return new self();
    }

    /**
     * @param MessageBuilder $messageToSend
     * @return $this
     */
    public function setMessageToSend(MessageBuilder $messageToSend)
    {
        $this->messageToSend = $messageToSend;

        return $this;
    }

    /**
     * @param Message $messageToReceive
     * @return $this
     */
    public function setMessageToReceive(Message $messageToReceive)
    {
        $this->messageToReceive = $messageToReceive;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setSendAndReceive(SubscribableChannel $requestChannel, ?PollableChannel $replyChannel, ?MessageChannel $errorChannel): void
    {
        // TODO: Implement setSendAndReceive() method.
    }

    /**
     * @inheritDoc
     */
    public function send(Message $message): void
    {
        // TODO: Implement send() method.
    }

    /**
     * @inheritDoc
     */
    public function prepareForSend(MessageBuilder $messageBuilder, InterfaceToCall $interfaceToCall): MessageBuilder
    {
        return $this->messageToSend ? $this->messageToSend : $messageBuilder;
    }

    /**
     * @inheritDoc
     */
    public function receiveReply(): ?Message
    {
        return $this->messageToReceive ? $this->messageToReceive : null;
    }
}