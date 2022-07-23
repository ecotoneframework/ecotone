<?php

namespace Ecotone\Messaging\Handler\Enricher;

use Ecotone\Messaging\Handler\RequestReplyProducer;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHandler;

/**
 * Class Enricher
 * @package Ecotone\Messaging\Handler\Enricher
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class Enricher implements MessageHandler
{
    private \Ecotone\Messaging\Handler\RequestReplyProducer $replyProducer;

    /**
     * Enricher constructor.
     *
     * @param RequestReplyProducer $replyProducer
     */
    public function __construct(RequestReplyProducer $replyProducer)
    {
        $this->replyProducer = $replyProducer;
    }

    /**
     * @inheritDoc
     */
    public function handle(Message $message): void
    {
        $this->replyProducer->handleWithReply($message);
    }

    public function __toString()
    {
        return 'Enricher - ' . $this->replyProducer;
    }
}
