<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway;

use ProxyManager\Factory\RemoteObject\AdapterInterface;
use SimplyCodedSoftware\IntegrationMessaging\Channel\DirectChannel;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\PollableChannel;
use SimplyCodedSoftware\IntegrationMessaging\SubscribableChannel;
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
     * @var array|GatewayParameterConverterBuilder[]
     */
    private $methodArgumentConverters = [];
    /**
     * @var CustomSendAndReceiveService
     */
    private $customSendAndReceiveService;
    /**
     * @var string
     */
    private $errorChannelName;
    /**
     * @var string[]
     */
    private $transactionFactoryReferenceNames = [];
    /**
     * @var string[]
     */
    private $requiredReferenceNames = [];

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
     * @param string $errorChannelName
     * @return GatewayProxyBuilder
     */
    public function withErrorChannel(string $errorChannelName) : self
    {
        $this->errorChannelName = $errorChannelName;

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
    public function getRequiredReferences(): array
    {
        return $this->requiredReferenceNames;
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
     * @param CustomSendAndReceiveService $sendAndReceiveService
     * @return GatewayProxyBuilder
     */
    public function withCustomSendAndReceiveService(CustomSendAndReceiveService $sendAndReceiveService) : self
    {
        $this->customSendAndReceiveService = $sendAndReceiveService;

        return $this;
    }

    /**
     * @param array $methodArgumentConverters
     * @return GatewayProxyBuilder
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function withParameterToMessageConverters(array $methodArgumentConverters): self
    {
        Assert::allInstanceOfType($methodArgumentConverters, GatewayParameterConverterBuilder::class);

        $this->methodArgumentConverters = $methodArgumentConverters;

        return $this;
    }

    /**
     * @param string[] $transactionFactoryReferenceNames
     * @return GatewayProxyBuilder
     */
    public function withTransactionFactories(array $transactionFactoryReferenceNames) : self
    {
        $this->transactionFactoryReferenceNames = $transactionFactoryReferenceNames;
        foreach ($transactionFactoryReferenceNames as $transactionFactoryReferenceName) {
            $this->requiredReferenceNames[] = $transactionFactoryReferenceName;
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function build(ReferenceSearchService $referenceSearchService, ChannelResolver $channelResolver)
    {
        Assert::isInterface($this->interfaceName, "Gateway should point to interface instead of got {$this->interfaceName}");

        $replyChannel = $this->replyChannelName ? $channelResolver->resolve($this->replyChannelName) : null;
        $requestChannel = $channelResolver->resolve($this->requestChannelName);
        /** @var DirectChannel $requestChannel */
        Assert::isSubclassOf($requestChannel, SubscribableChannel::class, "Gateway request channel ");
        if ($replyChannel) {
            /** @var PollableChannel $replyChannel */
            Assert::isSubclassOf($replyChannel, PollableChannel::class, "Reply channel must be pollable");
        }
        $errorChannel = $this->errorChannelName ? $channelResolver->resolve($this->errorChannelName) : null;

        $interfaceToCall = InterfaceToCall::create($this->interfaceName, $this->methodName);

        $replyReceiver = DefaultSendAndReceiveService::create($requestChannel, $replyChannel, $errorChannel);
        if ($this->customSendAndReceiveService) {
            $replyReceiver = $this->customSendAndReceiveService;
            $this->customSendAndReceiveService->setSendAndReceive($requestChannel, $replyChannel, $errorChannel);
        }
        if ($replyChannel) {
            $replyReceiver = new ChannelSendAndReceiveService($requestChannel, $replyChannel, $errorChannel);
        }
        if ($this->replyChannelName && $this->milliSecondsTimeout > 0) {
            $replyReceiver = new TimeoutChannelSendAndReceiveService($requestChannel, $replyChannel, $errorChannel, $this->milliSecondsTimeout);
        }

        if (!$interfaceToCall->hasReturnValue() && $this->replyChannelName) {
            throw InvalidArgumentException::create("Can't set reply channel for {$interfaceToCall}");
        }

        $methodArgumentConverters = [];
        foreach ($this->methodArgumentConverters as $messageConverterBuilder) {
            $methodArgumentConverters[] = $messageConverterBuilder->build();
        }

        $transactionFactories = [];
        foreach ($this->transactionFactoryReferenceNames as $referenceName) {
            $transactionFactories[] = $referenceSearchService->findByReference($referenceName);
        }

        $gateway = new Gateway(
            $this->interfaceName, $this->methodName,
            new MethodCallToMessageConverter(
                $this->interfaceName, $this->methodName, $methodArgumentConverters
            ),
            ErrorSendAndReceiveService::create($replyReceiver, $errorChannel),
            $transactionFactories
        );

        $factory = new \ProxyManager\Factory\RemoteObjectFactory(new class ($gateway) implements AdapterInterface {
            /**
             * @var Gateway
             */
            private $gatewayProxy;

            /**
             *  constructor.
             *
             * @param Gateway $gatewayProxy
             */
            public function __construct(Gateway $gatewayProxy)
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