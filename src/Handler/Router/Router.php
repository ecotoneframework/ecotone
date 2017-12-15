<?php

namespace Messaging\Handler\Router;

use Messaging\Handler\ChannelResolver;
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
class Router implements MessageHandler
{
    /**
     * @var string
     */
    private $handlerName;
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
     * RouterBuilder constructor.
     * @param string $handlerName
     * @param ChannelResolver $channelResolver
     * @param MessageChannel $inputChannel
     * @param MethodInvoker $methodInvoker
     */
    private function __construct(string $handlerName, ChannelResolver $channelResolver, MessageChannel $inputChannel, MethodInvoker $methodInvoker)
    {
        $this->handlerName = $handlerName;
        $this->channelResolver = $channelResolver;
        $this->inputChannel = $inputChannel;
        $this->methodInvoker = $methodInvoker;
    }

    /**
     * @param string $handlerName
     * @param ChannelResolver $channelResolver
     * @param MessageChannel $inputChannel
     * @param $objectToInvoke
     * @param string $methodName
     * @param array|MethodArgument[] $methodArguments
     * @return Router
     */
    public static function create(string $handlerName, ChannelResolver $channelResolver, MessageChannel $inputChannel, $objectToInvoke, string $methodName, array $methodArguments) : self
    {
        return new self($handlerName, $channelResolver, $inputChannel, MethodInvoker::createWith($objectToInvoke, $methodName, $methodArguments));
    }

    /**
     * @inheritDoc
     */
    public function handle(Message $message): void
    {
        $resultChannel = $this->methodInvoker->processMessage($message);

        $outputChannel = $this->channelResolver->resolve($resultChannel);

        $outputChannel->send($message);
    }
}