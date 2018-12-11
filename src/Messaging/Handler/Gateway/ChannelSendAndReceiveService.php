<?php

namespace SimplyCodedSoftware\Messaging\Handler\Gateway;

use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessageChannel;
use SimplyCodedSoftware\Messaging\PollableChannel;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;

/**
 * Class ReceivePoller
 * @package SimplyCodedSoftware\Messaging\Handler\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class ChannelSendAndReceiveService implements SendAndReceiveService
{
    /**
     * @var MessageChannel
     */
    private $requestChannel;
    /**
     * @var PollableChannel
     */
    private $replyChannel;
    /**
     * @var null|MessageChannel
     */
    private $errorChannel;

    /**
     * ReceivePoller constructor.
     * @param MessageChannel $requestChannel
     * @param PollableChannel $replyChannel
     * @param null|MessageChannel $errorChannel
     */
    public function __construct(MessageChannel $requestChannel, PollableChannel $replyChannel, ?MessageChannel $errorChannel)
    {
        $this->requestChannel = $requestChannel;
        $this->replyChannel = $replyChannel;
        $this->errorChannel = $errorChannel;
    }

    /**
     * @inheritDoc
     */
    public function send(Message $message): void
    {
        $this->requestChannel->send($message);
    }

    /**
     * @inheritDoc
     */
    public function prepareForSend(MessageBuilder $messageBuilder, InterfaceToCall $interfaceToCall): MessageBuilder
    {
        return $messageBuilder
                ->setErrorChannel($this->errorChannel ? $this->errorChannel : $this->replyChannel);
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
}