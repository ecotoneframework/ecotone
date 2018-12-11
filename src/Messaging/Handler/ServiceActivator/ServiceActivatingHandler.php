<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\ServiceActivator;

use SimplyCodedSoftware\Messaging\Handler\MessageProcessor;
use SimplyCodedSoftware\Messaging\Handler\RequestReplyProducer;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessageHandler;

/**
 * Class ServiceActivator
 * @package SimplyCodedSoftware\Messaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
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

    public function __toString()
    {
        return "Service Activator - " . (string)$this->requestReplyProducer;
    }
}