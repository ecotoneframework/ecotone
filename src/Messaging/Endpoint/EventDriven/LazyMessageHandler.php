<?php


namespace SimplyCodedSoftware\Messaging\Endpoint\EventDriven;

use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessageHandler;

/**
 * Class LazyMessageHandler
 * @package SimplyCodedSoftware\Messaging\Endpoint\EventDriven
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class LazyMessageHandler implements MessageHandler
{
    /**
     * @var MessageHandlerBuilder
     */
    private $messageHandlerBuilder;
    /**
     * @var ChannelResolver
     */
    private $channelResolver;
    /**
     * @var ReferenceSearchService
     */
    private $referenceSearchService;
    /**
     * @var MessageHandler
     */
    private $initializedMessageHandler;

    /**
     * LazyMessageHandler constructor.
     * @param MessageHandlerBuilder $messageHandlerBuilder
     * @param ChannelResolver $channelResolver
     * @param ReferenceSearchService $referenceSearchService
     */
    public function __construct(MessageHandlerBuilder $messageHandlerBuilder, ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService)
    {
        $this->messageHandlerBuilder = $messageHandlerBuilder;
        $this->channelResolver = $channelResolver;
        $this->referenceSearchService = $referenceSearchService;
    }

    /**
     * @inheritDoc
     */
    public function handle(Message $message): void
    {
        $this->getMessageHandler()->handle($message);
    }

    private function getMessageHandler() : MessageHandler
    {
        if (!$this->initializedMessageHandler) {
            $this->initializedMessageHandler = $this->messageHandlerBuilder->build($this->channelResolver, $this->referenceSearchService);
        }

        return $this->initializedMessageHandler;
    }
}