<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\ServiceActivator;

use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageProcessor;
use SimplyCodedSoftware\IntegrationMessaging\Handler\RequestReplyProducer;
use SimplyCodedSoftware\IntegrationMessaging\Message;
use SimplyCodedSoftware\IntegrationMessaging\MessageHandler;

/**
 * Class ServiceActivator
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
final class ServiceActivatingHandler implements MessageHandler
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
}