<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway;

use SimplyCodedSoftware\IntegrationMessaging\Channel\QueueChannel;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\IntegrationMessaging\Message;
use SimplyCodedSoftware\IntegrationMessaging\MessageChannel;
use SimplyCodedSoftware\IntegrationMessaging\PollableChannel;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;

/**
 * Class EmptyPoller
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class DefaultSendAndReceiveService implements SendAndReceiveService
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
     * DefaultReplySender constructor.
     * @param MessageChannel $requestChannel
     * @param PollableChannel $replyChannel
     * @param null|MessageChannel $errorChannel
     */
    private function __construct(MessageChannel $requestChannel, PollableChannel $replyChannel, ?MessageChannel $errorChannel)
    {
        $this->replyChannel = $replyChannel;
        $this->requestChannel = $requestChannel;
        $this->errorChannel = $errorChannel;
    }

    /**
     * @param MessageChannel $requestChannel
     * @param null|PollableChannel $replyChannel
     * @param null|MessageChannel $errorChannel
     * @return DefaultSendAndReceiveService
     */
    public static function create(MessageChannel $requestChannel, ?PollableChannel $replyChannel, ?MessageChannel $errorChannel) : self
    {
        return new self($requestChannel, $replyChannel ? $replyChannel : QueueChannel::create(), $errorChannel);
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
        if (!$interfaceToCall->hasReturnValue()) {
            return $messageBuilder;
        }

        return $messageBuilder
                ->setErrorChannel($this->errorChannel ? $this->errorChannel : $this->replyChannel)
                ->setReplyChannel($this->replyChannel);
    }

    /**
     * @inheritDoc
     */
    public function receiveReply(): ?Message
    {
        return $this->replyChannel->receive();
    }
}