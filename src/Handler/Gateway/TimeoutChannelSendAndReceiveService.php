<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway;

use SimplyCodedSoftware\IntegrationMessaging\Channel\DirectChannel;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\IntegrationMessaging\Message;
use SimplyCodedSoftware\IntegrationMessaging\MessageChannel;
use SimplyCodedSoftware\IntegrationMessaging\PollableChannel;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;

/**
 * Class TimeoutChannelReplySender
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class TimeoutChannelSendAndReceiveService implements SendAndReceiveService
{
    const MICROSECOND_TO_MILLI_SECOND = 1000;
    /**
     * @var PollableChannel
     */
    private $replyChannel;
    /**
     * @var int
     */
    private $millisecondsTimeout;
    /**
     * @var MessageChannel
     */
    private $requestChannel;
    /**
     * @var null|MessageChannel
     */
    private $errorChannel;

    /**
     * ReceivePoller constructor.
     * @param MessageChannel $requestChannel
     * @param PollableChannel $replyChannel
     * @param null|MessageChannel $errorChannel
     * @param int $millisecondsTimeout
     */
    public function __construct(MessageChannel $requestChannel, PollableChannel $replyChannel, ?MessageChannel $errorChannel, int $millisecondsTimeout)
    {
        $this->requestChannel = $requestChannel;
        $this->replyChannel = $replyChannel;
        $this->millisecondsTimeout = $millisecondsTimeout;
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
        $startingTimestamp = $this->currentMillisecond();

        while (($this->currentMillisecond() - $startingTimestamp) <= $this->millisecondsTimeout && is_null($message)) {
            $message = $this->replyChannel->receive();
        }

        return $message;
    }

    /**
     * @return float
     */
    private function currentMillisecond(): float
    {
        return microtime(true) * self::MICROSECOND_TO_MILLI_SECOND;
    }
}