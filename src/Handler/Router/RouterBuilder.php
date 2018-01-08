<?php

namespace SimplyCodedSoftware\Messaging\Handler\Router;

use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\MethodArgument;
use SimplyCodedSoftware\Messaging\MessageHandler;

/**
 * Class RouterBuilder
 * @package SimplyCodedSoftware\Messaging\Handler\Router
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class RouterBuilder implements MessageHandlerBuilder
{
    /**
     * @var string
     */
    private $handlerName;
    /**
     * @var string
     */
    private $inputMessageChannelName;
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
     * @var bool
     */
    private $resolutionRequired = true;

    /**
     * RouterBuilder constructor.
     * @param string $handlerName
     * @param string $inputChannel
     * @param object $objectToInvoke
     * @param string $methodName
     */
    private function __construct(string $handlerName, string $inputChannel, $objectToInvoke, string $methodName)
    {
        $this->handlerName = $handlerName;
        $this->inputMessageChannelName = $inputChannel;
        $this->objectToInvoke = $objectToInvoke;
        $this->methodName = $methodName;
    }

    /**
     * @param string $handlerName
     * @param string $inputChannelName
     * @param $objectToInvoke
     * @param string $methodName
     * @return RouterBuilder
     */
    public static function create(string $handlerName, string $inputChannelName, $objectToInvoke, string $methodName) : self
    {
        return new self($handlerName, $inputChannelName, $objectToInvoke, $methodName);
    }

    /**
     * @param string $handlerName
     * @param string $inputChannelName
     * @param array $typeToChannelMapping
     * @return RouterBuilder
     */
    public static function createPayloadTypeRouter(string $handlerName, string $inputChannelName, array $typeToChannelMapping) : self
    {
        return self::create($handlerName, $inputChannelName, PayloadTypeRouter::create($typeToChannelMapping), 'route');
    }

    /**
     * @param string $handlerName
     * @param string $inputChannelName
     * @param string $headerName
     * @param array $headerValueToChannelMapping
     * @return RouterBuilder
     */
    public static function createHeaderValueRouter(string $handlerName, string $inputChannelName, string $headerName, array $headerValueToChannelMapping) : self
    {
        return self::create($handlerName, $inputChannelName, HeaderValueRouter::create($headerName, $headerValueToChannelMapping), 'route');
    }

    /**
     * @inheritDoc
     */
    public function build(): MessageHandler
    {
        return Router::create(
            $this->channelResolver,
//            @TODO remoe input message channel
            $this->channelResolver->resolve($this->inputMessageChannelName),
            $this->objectToInvoke,
            $this->methodName,
            $this->resolutionRequired,
            $this->methodArguments
        );
    }

    /**
     * @param bool $resolutionRequired
     * @return RouterBuilder
     */
    public function setResolutionRequired(bool $resolutionRequired) : self
    {
        $this->resolutionRequired = $resolutionRequired;

        return $this;
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
    public function getInputMessageChannelName(): string
    {
        return $this->inputMessageChannelName;
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