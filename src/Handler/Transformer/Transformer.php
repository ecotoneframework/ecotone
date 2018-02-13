<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Transformer;

use SimplyCodedSoftware\IntegrationMessaging\MessageHandler;
use SimplyCodedSoftware\IntegrationMessaging\Handler\RequestReplyProducer;
use SimplyCodedSoftware\IntegrationMessaging\Message;

/**
 * Class TransformerHandler
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Transformer
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