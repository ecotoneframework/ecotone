<?php

namespace SimplyCodedSoftware\Messaging\Handler\Splitter;

use SimplyCodedSoftware\Messaging\Handler\RequestReplyProducer;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessageHandler;

/**
 * Class ServiceActivator
 * @package SimplyCodedSoftware\Messaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
final class Splitter implements MessageHandler
{
    /**
     * @var RequestReplyProducer
     */
    private $requestReplyProducer;

    /**
     * ServiceActivatingHandler constructor.
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
        return "Splitter - " . (string)$this->requestReplyProducer;
    }
}