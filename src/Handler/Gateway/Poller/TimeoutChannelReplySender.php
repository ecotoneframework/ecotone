<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\Poller;

use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\ReplySender;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\IntegrationMessaging\Message;
use SimplyCodedSoftware\IntegrationMessaging\PollableChannel;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;

/**
 * Class TimeoutChannelReplySender
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\Poller
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class TimeoutChannelReplySender implements ReplySender
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
     * ReceivePoller constructor.
     * @param PollableChannel $replyChannel
     * @param int $millisecondsTimeout
     */
    public function __construct(PollableChannel $replyChannel, int $millisecondsTimeout)
    {
        $this->replyChannel = $replyChannel;
        $this->millisecondsTimeout = $millisecondsTimeout;
    }

    /**
     * @inheritDoc
     */
    public function prepareFor(InterfaceToCall $interfaceToCall, MessageBuilder $messageBuilder): MessageBuilder
    {
        return $messageBuilder
                    ->setErrorChannel($this->replyChannel);
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

    /**
     * @inheritDoc
     */
    public function hasReply(): bool
    {
        return true;
    }
}