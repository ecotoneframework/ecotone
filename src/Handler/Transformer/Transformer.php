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
     * @var TransformerMessageProcessor
     */
    private $messageProcessor;
    /**
     * @var RequestReplyProducer
     */
    private $requestReplyProducer;

    /**
     * Transformer constructor.
     * @param MessageChannel $outputChannel
     * @param TransformerMessageProcessor $messageProcessor
     */
    public function __construct(MessageChannel $outputChannel, TransformerMessageProcessor $messageProcessor)
    {
        $this->requestReplyProducer = RequestReplyProducer::createWithNotRequiredReply($outputChannel);
        $this->messageProcessor = $messageProcessor;
    }

    /**
     * @inheritDoc
     */
    public function handle(Message $message): void
    {
        $this->requestReplyProducer->handleWithReply($message, $this->messageProcessor);
    }
}