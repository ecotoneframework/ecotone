<?php

namespace Ecotone\Amqp;

use Ecotone\Messaging\Message;
use Ecotone\Messaging\PollableChannel;

class AmqpBackendMessageChannel implements PollableChannel
{
    /**
     * @var AmqpInboundChannelAdapter
     */
    private $amqpInboundChannelAdapter;
    /**
     * @var AmqpOutboundChannelAdapter
     */
    private $amqpOutboundChannelAdapter;

    /**
     * AmqpBackedQueue constructor.
     *
     * @param AmqpInboundChannelAdapter $amqpInboundChannelAdapter
     * @param AmqpOutboundChannelAdapter $amqpOutboundChannelAdapter
     */
    public function __construct(AmqpInboundChannelAdapter $amqpInboundChannelAdapter, AmqpOutboundChannelAdapter $amqpOutboundChannelAdapter)
    {
        $this->amqpInboundChannelAdapter = $amqpInboundChannelAdapter;
        $this->amqpOutboundChannelAdapter = $amqpOutboundChannelAdapter;
    }

    /**
     * @inheritDoc
     */
    public function send(Message $message): void
    {
        $this->amqpOutboundChannelAdapter->handle($message);
    }

    /**
     * @inheritDoc
     */
    public function receive(): ?Message
    {
        return $this->amqpInboundChannelAdapter->getMessage();
    }

    /**
     * @inheritDoc
     */
    public function receiveWithTimeout(int $timeoutInMilliseconds): ?Message
    {
        return $this->amqpInboundChannelAdapter->getMessage();
    }
}
