<?php

namespace Ecotone\Messaging\Handler\Transformer;

use Ecotone\Messaging\MessageHandler;
use Ecotone\Messaging\Handler\RequestReplyProducer;
use Ecotone\Messaging\Message;

/**
 * Class TransformerHandler
 * @package Ecotone\Messaging\Handler\Transformer
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class Transformer implements MessageHandler
{
    private \Ecotone\Messaging\Handler\RequestReplyProducer $requestReplyProducer;
    /**
     * Transformer constructor.
     * @param RequestReplyProducer $requestReplyProducer
     */
    public function __construct(RequestReplyProducer $requestReplyProducer)
    {
        $this->requestReplyProducer = $requestReplyProducer;
    }

    /**
     * @inheritDoc
     */
    public function handle(Message $message): void
    {
        $this->requestReplyProducer->handleWithReply($message);
    }

    public function __toString()
    {
        return "Transformer - " . $this->requestReplyProducer;
    }
}