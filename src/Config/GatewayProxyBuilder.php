<?php

namespace Messaging\Config;

use Messaging\Channel\DirectChannel;
use Messaging\Handler\Gateway\Gateway;
use Messaging\Handler\Gateway\GatewayProxy;
use Messaging\Handler\Gateway\GatewayReply;
use Messaging\Handler\Gateway\MethodArgumentConverter;
use Messaging\Handler\Gateway\MethodCallToMessageConverter;
use Messaging\Handler\Gateway\Poller\ChannelReplySender;
use Messaging\Handler\Gateway\Poller\EmptyReplySender;
use Messaging\Handler\Gateway\Poller\TimeoutChannelReplySender;
use Messaging\Handler\Gateway\ReplySender;
use Messaging\PollableChannel;
use Messaging\Support\Assert;

/**
 * Class GatewayProxySpec
 * @package Messaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class GatewayProxyBuilder
{
    /**
     * @var string
     */
    private $interfaceName;
    /**
     * @var string
     */
    private $methodName;
    /**
     * @var DirectChannel
     */
    private $requestChannel;
    /**
     * @var int
     */
    private $milliSecondsTimeout;
    /**
     * @var PollableChannel
     */
    private $replyChannel;
    /**
     * @var array|MethodArgumentConverter[]
     */
    private $methodArgumentConverters = [];

    /**
     * GatewayProxyBuilder constructor.
     * @param string $interfaceName
     * @param string $methodName
     * @param DirectChannel $requestChannel
     */
    private function __construct(string $interfaceName, string $methodName, DirectChannel $requestChannel)
    {
        $this->interfaceName = $interfaceName;
        $this->methodName = $methodName;
        $this->requestChannel = $requestChannel;
    }

    /**
     * @param PollableChannel $replyChannel where to expect reply
     */
    public function withReplyChannel(PollableChannel $replyChannel) : void
    {
        $this->replyChannel = $replyChannel;
    }

    /**
     * @param int $millisecondsTimeout
     */
    public function withMillisecondTimeout(int $millisecondsTimeout) : void
    {
        $this->milliSecondsTimeout = $millisecondsTimeout;
    }

    /**
     * @param array $methodArgumentConverters
     */
    public function withMethodArgumentConverters(array $methodArgumentConverters) : void
    {
        Assert::allInstanceOfType($methodArgumentConverters, MethodArgumentConverter::class);

        $this->methodArgumentConverters = $methodArgumentConverters;
    }

    /**
     * @param string $interfaceName
     * @param string $methodName
     * @param DirectChannel $requestChannel
     * @return GatewayProxyBuilder
     */
    public static function create(string $interfaceName, string $methodName, DirectChannel $requestChannel) : self
    {
        return new self($interfaceName, $methodName, $requestChannel);
    }

    /**
     * @return GatewayProxy
     */
    public function build() : GatewayProxy
    {
        $replySender = new EmptyReplySender();
        if ($this->replyChannel) {
            $replySender = new ChannelReplySender($this->replyChannel);
        }
        if ($this->replyChannel && $this->milliSecondsTimeout > 0) {
            $replySender = new TimeoutChannelReplySender($this->replyChannel, $this->milliSecondsTimeout);
        }

        return new GatewayProxy(
            $this->interfaceName, $this->methodName,
            new MethodCallToMessageConverter(
                $this->interfaceName, $this->methodName, $this->methodArgumentConverters
            ),
            $replySender,
            $this->requestChannel
        );
    }
}