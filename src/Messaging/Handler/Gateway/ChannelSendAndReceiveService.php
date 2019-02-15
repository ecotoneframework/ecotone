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
     * @var int
     */
    private $replyMilliSecondsTimeout;

    /**
     * ReceivePoller constructor.
     * @param MessageChannel $requestChannel
     * @param PollableChannel $replyChannel
     * @param null|MessageChannel $errorChannel
     * @param int $replyMilliSecondsTimeout
     */
    public function __construct(MessageChannel $requestChannel, PollableChannel $replyChannel, ?MessageChannel $errorChannel, int $replyMilliSecondsTimeout)
    {
        $this->requestChannel = $requestChannel;
        $this->replyChannel = $replyChannel;
        $this->errorChannel = $errorChannel;
        $this->replyMilliSecondsTimeout = $replyMilliSecondsTimeout;
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
        return $messageBuilder;
        return $messageBuilder
                ->setErrorChannel($this->errorChannel ? $this->errorChannel : $this->replyChannel);
    }

    /**
     * @inheritDoc
     */
    public function receiveReply(): ?Message
    {
        return $this->replyMilliSecondsTimeout > 0 ? $this->replyChannel->receiveWithTimeout($this->replyMilliSecondsTimeout) : $this->replyChannel->receive();
    }
}