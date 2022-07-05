<?php

namespace Ecotone\Messaging\Handler\Router;

use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\DestinationResolutionException;
use Ecotone\Messaging\Handler\MessageProcessor;
use Ecotone\Messaging\Handler\ParameterConverter;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvoker;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageChannel;
use Ecotone\Messaging\MessageHandler;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * Class Router
 * @package Ecotone\Messaging\Handler\Router
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
final class Router implements MessageHandler
{
    private \Ecotone\Messaging\Handler\ChannelResolver $channelResolver;
    private \Ecotone\Messaging\Handler\MessageProcessor $methodInvoker;
    private bool $isResolutionRequired;
    private ?string $defaultResolutionChannelName;
    private bool $applySequence;

    /**
     * RouterBuilder constructor.
     *
     * @param ChannelResolver $channelResolver
     * @param MessageProcessor   $methodInvoker
     * @param bool            $isResolutionRequired
     * @param null|string     $defaultResolutionChannelName
     * @param bool            $applySequence
     */
    private function __construct(ChannelResolver $channelResolver, MessageProcessor $methodInvoker, bool $isResolutionRequired, ?string $defaultResolutionChannelName, bool $applySequence)
    {
        $this->channelResolver = $channelResolver;
        $this->methodInvoker = $methodInvoker;
        $this->isResolutionRequired = $isResolutionRequired;
        $this->defaultResolutionChannelName = $defaultResolutionChannelName;
        $this->applySequence = $applySequence;
    }

    /**
     * @param ChannelResolver $channelResolver
     * @param MessageProcessor $messageProcessor
     * @param bool $isResolutionRequired
     * @param null|string $defaultResolutionChannel
     *
     * @param bool $applySequence
     *
     * @return Router
     */
    public static function create(ChannelResolver $channelResolver, MessageProcessor $messageProcessor, bool $isResolutionRequired, ?string $defaultResolutionChannel, bool $applySequence) : self
    {
        return new self($channelResolver, $messageProcessor, $isResolutionRequired, $defaultResolutionChannel, $applySequence);
    }

    /**
     * @inheritDoc
     */
    public function handle(Message $message): void
    {
        $resolutionChannels = $this->methodInvoker->processMessage($message);

        if (is_null($resolutionChannels)) {
            $resolutionChannels = [];
        }
        if (!is_array($resolutionChannels)) {
            $resolutionChannels = [$resolutionChannels];
        }
        if (empty($resolutionChannels) && $this->defaultResolutionChannelName) {
            $resolutionChannels[] = $this->defaultResolutionChannelName;
        }

        if (empty($resolutionChannels) && $this->isResolutionRequired) {
            throw DestinationResolutionException::create("Can't resolve destination, because there are no channels to send message to.");
        }

        $resolutionChannels = array_unique($resolutionChannels);
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
        return "Router - " . $this->methodInvoker;
    }
}