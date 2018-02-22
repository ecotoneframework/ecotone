<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher;

use SimplyCodedSoftware\IntegrationMessaging\Handler\ChannelResolver;
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
     * @var ExpressionEvaluationService
     */
    private $expressionEvaluationService;
    /**
     * @var MessageProcessor
     */
    private $messageProcessor;
    /**
     * @var ChannelResolver
     */
    private $channelResolver;

    /**
     * Enricher constructor.
     *
     * @param MessageProcessor            $messageProcessor
     * @param ChannelResolver             $channelResolver
     * @param ExpressionEvaluationService $expressionEvaluationService
     */
    private function __construct(MessageProcessor $messageProcessor, ChannelResolver $channelResolver, ExpressionEvaluationService $expressionEvaluationService)
    {
        $this->expressionEvaluationService = $expressionEvaluationService;
        $this->messageProcessor = $messageProcessor;
        $this->channelResolver = $channelResolver;
    }

    /**
     * @param MessageProcessor            $messageProcessor
     * @param ChannelResolver             $channelResolver
     * @param ExpressionEvaluationService $expressionEvaluationService
     *
     * @return Enricher
     */
    public static function create(MessageProcessor $messageProcessor, ChannelResolver $channelResolver, ExpressionEvaluationService $expressionEvaluationService) : self
    {
        return new self($messageProcessor, $channelResolver, $expressionEvaluationService);
    }

    /**
     * @inheritDoc
     */
    public function handle(Message $message): void
    {
        $requestMessage = MessageBuilder::fromMessage($message);

        $replyMessage = $this->messageProcessor->processMessage($message);

        if ($message->getHeaders()->containsKey(MessageHeaders::REPLY_CHANNEL)) {
            $replyChannel = $this->channelResolver->resolve($message->getHeaders()->getReplyChannel());

            $replyChannel->send($replyMessage);
        }
    }
}