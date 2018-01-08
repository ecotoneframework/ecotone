<?php

namespace SimplyCodedSoftware\Messaging\Handler\Transformer;

use SimplyCodedSoftware\Messaging\MessageHandler;
use SimplyCodedSoftware\Messaging\Handler\RequestReplyProducer;
use SimplyCodedSoftware\Messaging\Message;

/**
 * Class TransformerHandler
 * @package SimplyCodedSoftware\Messaging\Handler\Transformer
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