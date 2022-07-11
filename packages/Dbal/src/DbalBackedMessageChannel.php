<?php

namespace Ecotone\Dbal;

use Ecotone\Messaging\Message;
use Ecotone\Messaging\PollableChannel;

class DbalBackedMessageChannel implements PollableChannel
{
    /**
     * @var DbalInboundChannelAdapter
     */
    private $inboundChannelAdapter;
    /**
     * @var DbalOutboundChannelAdapter
     */
    private $outboundChannelAdapter;

    public function __construct(DbalInboundChannelAdapter $inboundChannelAdapter, DbalOutboundChannelAdapter $outboundChannelAdapter)
    {
        $this->inboundChannelAdapter = $inboundChannelAdapter;
        $this->outboundChannelAdapter = $outboundChannelAdapter;
    }

    /**
     * @inheritDoc
     */
    public function send(Message $message): void
    {
        $this->outboundChannelAdapter->handle($message);
    }

    /**
     * @inheritDoc
     */
    public function receive(): ?Message
    {
        return $this->inboundChannelAdapter->receiveMessage();
    }

    /**
     * @inheritDoc
     */
    public function receiveWithTimeout(int $timeoutInMilliseconds): ?Message
    {
        return $this->inboundChannelAdapter->receiveMessage();
    }
}
