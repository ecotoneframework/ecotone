<?php


namespace Ecotone\Messaging\Endpoint\EventDriven;

use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\MessageHandlerBuilder;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHandler;

/**
 * Class LazyMessageHandler
 * @package Ecotone\Messaging\Endpoint\EventDriven
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class LazyMessageHandler implements MessageHandler
{
    private \Ecotone\Messaging\Handler\MessageHandlerBuilder $messageHandlerBuilder;
    private \Ecotone\Messaging\Handler\ChannelResolver $channelResolver;
    private \Ecotone\Messaging\Handler\ReferenceSearchService $referenceSearchService;
    private ?\Ecotone\Messaging\MessageHandler $initializedMessageHandler = null;

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

    public function __toString()
    {
        return (string)$this->messageHandlerBuilder;
    }
}