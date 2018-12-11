<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Chain;

use SimplyCodedSoftware\Messaging\Handler\RequestReplyProducer;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessageHandler;

/**
 * Class ChainHandler
 * @package SimplyCodedSoftware\Messaging\Handler\Chain
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ChainHandler implements MessageHandler
{
    /**
     * @var RequestReplyProducer
     */
    private $requestReplyProducer;

    /**
     * ChainHandler constructor.
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