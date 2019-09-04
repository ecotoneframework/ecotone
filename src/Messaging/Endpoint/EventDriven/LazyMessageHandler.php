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

    public function __toString()
    {
        return (string)$this->messageHandlerBuilder;
    }
}