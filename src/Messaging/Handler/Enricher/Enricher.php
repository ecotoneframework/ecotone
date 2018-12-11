<?php

namespace SimplyCodedSoftware\Messaging\Handler\Enricher;

use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\ExpressionEvaluationService;
use SimplyCodedSoftware\Messaging\Handler\MessageProcessor;
use SimplyCodedSoftware\Messaging\Handler\RequestReplyProducer;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessageHandler;
use SimplyCodedSoftware\Messaging\MessageHeaders;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;

/**
 * Class Enricher
 * @package SimplyCodedSoftware\Messaging\Handler\Enricher
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class Enricher implements MessageHandler
{
    /**
     * @var RequestReplyProducer
     */
    private $replyProducer;

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
        return "Enricher - " . (string)$this->replyProducer;
    }
}