<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\Receiver;

use SimplyCodedSoftware\IntegrationMessaging\Channel\DirectChannel;
use SimplyCodedSoftware\IntegrationMessaging\Channel\QueueChannel;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\SendAndReceiveService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\IntegrationMessaging\Message;
use SimplyCodedSoftware\IntegrationMessaging\MessageChannel;
use SimplyCodedSoftware\IntegrationMessaging\PollableChannel;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;

/**
 * Class EmptyPoller
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\Receiver
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class DefaultSendAndReceiveService implements SendAndReceiveService
{
    /**
     * @var DirectChannel
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
     * @param DirectChannel $requestChannel
     * @param PollableChannel $replyChannel
     * @param null|MessageChannel $errorChannel
     */
    private function __construct(DirectChannel $requestChannel, PollableChannel $replyChannel, ?MessageChannel $errorChannel)
    {
        $this->replyChannel = $replyChannel;
        $this->requestChannel = $requestChannel;
        $this->errorChannel = $errorChannel;
    }

    /**
     * @param DirectChannel $requestChannel
     * @param null|PollableChannel $replyChannel
     * @param null|MessageChannel $errorChannel
     * @return DefaultSendAndReceiveService
     */
    public static function create(DirectChannel $requestChannel, ?PollableChannel $replyChannel, ?MessageChannel $errorChannel) : self
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
        if ($interfaceToCall->doesItNotReturnValue()) {
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