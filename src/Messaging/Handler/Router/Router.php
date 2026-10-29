<?php

namespace Ecotone\Messaging\Handler\Router;

use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\DestinationResolutionException;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHandler;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * Class Router
 * @package Ecotone\Messaging\Handler\Router
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 * @internal
 */
/**
 * licence Apache-2.0
 */
final class Router implements MessageHandler
{
    public function __construct(
        private ChannelResolver $channelResolver,
        private RouteSelector   $routeSelector,
        private bool            $isResolutionRequired,
        private ?string         $defaultResolutionChannelName,
        private bool            $applySequence
    ) {
    }

    /**
     * @inheritDoc
     */
    public function handle(Message $message): void
    {
        $resolutionChannels = $this->routeSelector->route($message);

        if (empty($resolutionChannels) && $this->defaultResolutionChannelName) {
            $resolutionChannels[] = $this->defaultResolutionChannelName;
        }

        if (empty($resolutionChannels) && $this->isResolutionRequired) {
            throw DestinationResolutionException::create("Can't resolve destination, because there are no channels to send message to.");
        }

        $sequenceSize = count($resolutionChannels);
        $sequenceNumber = 1;
        foreach ($resolutionChannels as $resolutionChannel) {
            $outputChannel = $this->channelResolver->resolve($resolutionChannel);

            $messageToSend = $this->applySequence
                                ? MessageBuilder::fromMessage($message)
                                    ->setHeader(MessageHeaders::SEQUENCE_NUMBER, $sequenceNumber)
                                    ->setHeader(MessageHeaders::SEQUENCE_SIZE, $sequenceSize)
                                    ->build()
                                : $message;

            $outputChannel->send($messageToSend);
            $sequenceNumber++;
        }
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return 'Router - ' . $this->routeSelector;
    }
}
