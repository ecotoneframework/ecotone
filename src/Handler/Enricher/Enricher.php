<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher;

use SimplyCodedSoftware\IntegrationMessaging\Handler\ChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ExpressionEvaluationService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageProcessor;
use SimplyCodedSoftware\IntegrationMessaging\Handler\RequestReplyProducer;
use SimplyCodedSoftware\IntegrationMessaging\Message;
use SimplyCodedSoftware\IntegrationMessaging\MessageHandler;
use SimplyCodedSoftware\IntegrationMessaging\MessageHeaders;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;

/**
 * Class Enricher
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher
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