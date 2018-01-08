<?php

namespace SimplyCodedSoftware\Messaging\Handler\Router;

use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\DestinationResolutionException;
use SimplyCodedSoftware\Messaging\Handler\MethodArgument;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MethodInvoker;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessageChannel;
use SimplyCodedSoftware\Messaging\MessageHandler;

/**
 * Class Router
 * @package SimplyCodedSoftware\Messaging\Handler\Router
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
final class Router implements MessageHandler
{
    /**
     * @var MessageChannel
     */
    private $inputChannel;
    /**
     * @var ChannelResolver
     */
    private $channelResolver;
    /**
     * @var MethodInvoker
     */
    private $methodInvoker;
    /**
     * @var bool
     */
    private $isResolutionRequired;

    /**
     * RouterBuilder constructor.
     * @param ChannelResolver $channelResolver
     * @param MessageChannel $inputChannel
     * @param MethodInvoker $methodInvoker
     * @param bool $isResolutionRequired
     */
    private function __construct(ChannelResolver $channelResolver, MessageChannel $inputChannel, MethodInvoker $methodInvoker, bool $isResolutionRequired)
    {
        $this->channelResolver = $channelResolver;
        $this->inputChannel = $inputChannel;
        $this->methodInvoker = $methodInvoker;
        $this->isResolutionRequired = $isResolutionRequired;
    }

    /**
     * @param ChannelResolver $channelResolver
     * @param MessageChannel $inputChannel
     * @param $objectToInvoke
     * @param string $methodName
     * @param bool $isResolutionRequired
     * @param array|MethodArgument[] $methodArguments
     * @return Router
     */
    public static function create(ChannelResolver $channelResolver, MessageChannel $inputChannel, $objectToInvoke, string $methodName, bool $isResolutionRequired, array $methodArguments) : self
    {
        return new self($channelResolver, $inputChannel, MethodInvoker::createWith($objectToInvoke, $methodName, $methodArguments), $isResolutionRequired);
    }

    /**
     * @inheritDoc
     */
    public function handle(Message $message): void
    {
        $resolutionChannels = $this->methodInvoker->processMessage($message);

        if (!is_array($resolutionChannels)) {
            $resolutionChannels = [$resolutionChannels];
        }

        if (empty($resolutionChannels) && $this->isResolutionRequired) {
            throw DestinationResolutionException::create("Can't resolve destination, because there are no channels to send message to.");
        }

        foreach ($resolutionChannels as $resolutionChannel) {
            $outputChannel = $this->channelResolver->resolve($resolutionChannel);

            $outputChannel->send($message);
        }
    }
}