<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway;

use ProxyManager\Factory\RemoteObject\AdapterInterface;
use SimplyCodedSoftware\IntegrationMessaging\Channel\DirectChannel;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\Poller\ChannelReplySender;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\Poller\DefaultReplySender;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\Poller\ErrorReplySender;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\Poller\TimeoutChannelReplySender;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\IntegrationMessaging\PollableChannel;
use SimplyCodedSoftware\IntegrationMessaging\Support\Assert;
use SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException;

/**
 * Class GatewayProxySpec
 * @package SimplyCodedSoftware\IntegrationMessaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class GatewayProxyBuilder implements GatewayBuilder
{
    /**
     * @var string
     */
    private $referenceName;
    /**
     * @var string
     */
    private $interfaceName;
    /**
     * @var string
     */
    private $methodName;
    /**
     * @var string
     */
    private $requestChannelName;
    /**
     * @var int
     */
    private $milliSecondsTimeout;
    /**
     * @var string
     */
    private $replyChannelName;
    /**
     * @var array|ParameterToMessageConverterBuilder[]
     */
    private $methodArgumentConverters = [];

    /**
     * GatewayProxyBuilder constructor.
     * @param string $referenceName
     * @param string $interfaceName
     * @param string $methodName
     * @param string $requestChannelName
     */
    private function __construct(string $referenceName, string $interfaceName, string $methodName, string $requestChannelName)
    {
        $this->referenceName = $referenceName;
        $this->interfaceName = $interfaceName;
        $this->methodName = $methodName;
        $this->requestChannelName = $requestChannelName;
    }

    /**
     * @param string $referenceName
     * @param string $interfaceName
     * @param string $methodName
     * @param string $requestChannelName
     * @return GatewayProxyBuilder
     */
    public static function create(string $referenceName, string $interfaceName, string $methodName, string $requestChannelName): self
    {
        return new self($referenceName, $interfaceName, $methodName, $requestChannelName);
    }

    /**
     * @param string $replyChannelName where to expect reply
     * @return GatewayProxyBuilder
     */
    public function withReplyChannel(string $replyChannelName): self
    {
        $this->replyChannelName = $replyChannelName;

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
     * @inheritDoc
     */
    public function getReferenceName(): string
    {
        return $this->referenceName;
    }

    /**
     * @inheritDoc
     */
    public function getRequestChannelName(): string
    {
        return $this->requestChannelName;
    }

    /**
     * @inheritDoc
     */
    public function getInterfaceName(): string
    {
        return $this->interfaceName;
    }

    /**
     * @param array $methodArgumentConverters
     * @return GatewayProxyBuilder
     */
    public function withParameterToMessageConverters(array $methodArgumentConverters): self
    {
        Assert::allInstanceOfType($methodArgumentConverters, ParameterToMessageConverterBuilder::class);

        $this->methodArgumentConverters = $methodArgumentConverters;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function build(ChannelResolver $channelResolver)
    {
        $replyChannel = $this->replyChannelName ? $channelResolver->resolve($this->replyChannelName) : null;
        $interfaceToCall = InterfaceToCall::create($this->interfaceName, $this->methodName);

        $replySender = DefaultReplySender::create();
        if ($replyChannel) {
            /** @var PollableChannel $replyChannel */
            Assert::isSubclassOf($replyChannel, PollableChannel::class, "Reply channel must be pollable");

            $replySender = new ChannelReplySender($replyChannel);
        }
        if ($this->replyChannelName && $this->milliSecondsTimeout > 0) {
            $replySender = new TimeoutChannelReplySender($replyChannel, $this->milliSecondsTimeout);
        }

        /** @var DirectChannel $requestChannel */
        $requestChannel = $channelResolver->resolve($this->requestChannelName);
        Assert::isSubclassOf($requestChannel, DirectChannel::class, "Gateway request channel ");

        if ($interfaceToCall->doesItNotReturnValue() && $this->replyChannelName) {
            throw InvalidArgumentException::create("Can't set reply channel for {$interfaceToCall}");
        }

        $methodArgumentConverters = [];
        foreach ($this->methodArgumentConverters as $messageConverterBuilder) {
            $methodArgumentConverters[] = $messageConverterBuilder->build();
        }

        $gatewayProxy = new GatewayProxy(
            $this->interfaceName, $this->methodName,
            new MethodCallToMessageConverter(
                $this->interfaceName, $this->methodName, $methodArgumentConverters
            ),
            ErrorReplySender::create($replySender),
            $requestChannel
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

    public function __toString()
    {
        return "gateway proxy";
    }
}