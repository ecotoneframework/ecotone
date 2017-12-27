<?php

namespace Messaging\Handler\Transformer;

use Messaging\Handler\RequestReplyProducer;
use Messaging\Message;
use Messaging\MessageChannel;
use Messaging\MessageHandler;

/**
 * Class TransformerHandler
 * @package Messaging\Handler\Transformer
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class Transformer implements MessageHandler
{
    /**
     * @var RequestReplyProducer
     */
    private $requestReplyProducer;
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
}