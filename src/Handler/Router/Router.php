<?php

namespace Messaging\Handler\Router;

use Messaging\Handler\ChannelResolver;
use Messaging\Handler\DestinationResolutionException;
use Messaging\Handler\MethodArgument;
use Messaging\Handler\Processor\MethodInvoker\MethodInvoker;
use Messaging\Message;
use Messaging\MessageChannel;
use Messaging\MessageHandler;

/**
 * Class Router
 * @package Messaging\Handler\Router
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