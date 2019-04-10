<?php

namespace SimplyCodedSoftware\Messaging\Handler\Router;

use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\DestinationResolutionException;
use SimplyCodedSoftware\Messaging\Handler\MessageProcessor;
use SimplyCodedSoftware\Messaging\Handler\ParameterConverter;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MethodInvoker;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessageChannel;
use SimplyCodedSoftware\Messaging\MessageHandler;
use SimplyCodedSoftware\Messaging\MessageHeaders;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;

/**
 * Class Router
 * @package SimplyCodedSoftware\Messaging\Handler\Router
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
final class Router implements MessageHandler
{
    /**
     * @var ChannelResolver
     */
    private $channelResolver;
    /**
     * @var MessageProcessor
     */
    private $methodInvoker;
    /**
     * @var bool
     */
    private $isResolutionRequired;
    /**
     * @var null|string
     */
    private $defaultResolutionChannelName;
    /**
     * @var bool
     */
    private $applySequence;

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
        return "Router - " . (string)$this->methodInvoker;
    }
}