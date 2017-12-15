<?php

namespace Messaging\Handler\Router;

use Messaging\Handler\ChannelResolver;
use Messaging\Handler\MessageHandlerBuilder;
use Messaging\Handler\MethodArgument;
use Messaging\MessageChannel;
use Messaging\MessageHandler;

/**
 * Class RouterBuilder
 * @package Messaging\Handler\Router
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class RouterBuilder implements MessageHandlerBuilder
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
     * @var object
     */
    private $objectToInvoke;
    /**
     * @var string
     */
    private $methodName;
    /**
     * @var ChannelResolver
     */
    private $channelResolver;
    /**
     * @var array|MethodArgument[]
     */
    private $methodArguments = [];

    /**
     * RouterBuilder constructor.
     * @param string $handlerName
     * @param MessageChannel $inputChannel
     * @param object $objectToInvoke
     * @param string $methodName
     */
    private function __construct(string $handlerName, MessageChannel $inputChannel, $objectToInvoke, string $methodName)
    {
        $this->handlerName = $handlerName;
        $this->inputChannel = $inputChannel;
        $this->objectToInvoke = $objectToInvoke;
        $this->methodName = $methodName;
    }

    /**
     * @param string $handlerName
     * @param MessageChannel $inputChannel
     * @param $objectToInvoke
     * @param string $methodName
     * @return RouterBuilder
     */
    public static function create(string $handlerName, MessageChannel $inputChannel, $objectToInvoke, string $methodName) : self
    {
        return new self($handlerName, $inputChannel, $objectToInvoke, $methodName);
    }

    /**
     * @inheritDoc
     */
    public function build(): MessageHandler
    {
        return Router::create(
            $this->messageHandlerName(),
            $this->channelResolver,
            $this->inputChannel,
            $this->objectToInvoke,
            $this->methodName,
            $this->methodArguments
        );
    }

    /**
     * @inheritDoc
     */
    public function messageHandlerName(): string
    {
        return $this->handlerName;
    }

    /**
     * @inheritDoc
     */
    public function getInputMessageChannel(): MessageChannel
    {
        return $this->inputChannel;
    }

    /**
     * @inheritDoc
     */
    public function setChannelResolver(ChannelResolver $channelResolver): MessageHandlerBuilder
    {
        $this->channelResolver = $channelResolver;

        return $this;
    }
}