<?php

namespace Messaging\Handler\Gateway;

use Messaging\Channel\DirectChannel;
use Messaging\Handler\Gateway\Poller\ChannelReplySender;
use Messaging\Handler\Gateway\Poller\EmptyReplySender;
use Messaging\Handler\Gateway\Poller\TimeoutChannelReplySender;
use Messaging\PollableChannel;
use Messaging\Support\Assert;
use ProxyManager\Factory\RemoteObject\AdapterInterface;

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
     * @param string $interfaceName
     * @param string $methodName
     * @param DirectChannel $requestChannel
     * @return GatewayProxyBuilder
     */
    public static function create(string $interfaceName, string $methodName, DirectChannel $requestChannel): self
    {
        return new self($interfaceName, $methodName, $requestChannel);
    }

    /**
     * @param PollableChannel $replyChannel where to expect reply
     * @return GatewayProxyBuilder
     */
    public function withReplyChannel(PollableChannel $replyChannel): self
    {
        $this->replyChannel = $replyChannel;

        return $this;
    }

    /**
     * @param int $millisecondsTimeout
     * @return GatewayProxyBuilder
     */
    public function withMillisecondTimeout(int $millisecondsTimeout): self
    {
        $this->milliSecondsTimeout = $millisecondsTimeout;

        return $this;
    }

    /**
     * @param array $methodArgumentConverters
     * @return GatewayProxyBuilder
     */
    public function withMethodArgumentConverters(array $methodArgumentConverters): self
    {
        Assert::allInstanceOfType($methodArgumentConverters, MethodArgumentConverter::class);

        $this->methodArgumentConverters = $methodArgumentConverters;

        return $this;
    }

    /**
     * Returns proxy to passed interface
     *
     * @return mixed
     */
    public function build()
    {
        $replySender = new EmptyReplySender();
        if ($this->replyChannel) {
            $replySender = new ChannelReplySender($this->replyChannel);
        }
        if ($this->replyChannel && $this->milliSecondsTimeout > 0) {
            $replySender = new TimeoutChannelReplySender($this->replyChannel, $this->milliSecondsTimeout);
        }

        $gatewayProxy = new GatewayProxy(
            $this->interfaceName, $this->methodName,
            new MethodCallToMessageConverter(
                $this->interfaceName, $this->methodName, $this->methodArgumentConverters
            ),
            ErrorReplySender::create($replySender),
            $this->requestChannel
        );

        $factory = new \ProxyManager\Factory\RemoteObjectFactory(new class ($gatewayProxy) implements AdapterInterface {
            /**
             * @var GatewayProxy
             */
            private $gatewayProxy;

            /**
             *  constructor.
             * @param GatewayProxy $gatewayProxy
             */
            public function __construct(GatewayProxy $gatewayProxy)
            {
                $this->gatewayProxy = $gatewayProxy;
            }

            /**
             * @inheritDoc
             */
            public function call(string $wrappedClass, string $method, array $params = [])
            {
                return $this->gatewayProxy->execute($params);
            }
        });

        return $factory->createProxy($this->interfaceName);
    }
}