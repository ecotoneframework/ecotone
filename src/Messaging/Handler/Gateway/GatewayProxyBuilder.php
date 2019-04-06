<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Gateway;

use ProxyManager\Factory\RemoteObject\AdapterInterface;
use ProxyManager\Factory\RemoteObjectFactory;
use SimplyCodedSoftware\Messaging\Channel\DirectChannel;
use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCallRegistry;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\PollableChannel;
use SimplyCodedSoftware\Messaging\SubscribableChannel;
use SimplyCodedSoftware\Messaging\Support\Assert;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;

/**
 * Class GatewayProxySpec
 * @package SimplyCodedSoftware\Messaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class GatewayProxyBuilder implements GatewayBuilder
{
    const DEFAULT_REPLY_MILLISECONDS_TIMEOUT = -1;

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
    private $replyMilliSecondsTimeout = self::DEFAULT_REPLY_MILLISECONDS_TIMEOUT;
    /**
     * @var string
     */
    private $replyChannelName;
    /**
     * @var array|GatewayParameterConverterBuilder[]
     */
    private $methodArgumentConverters = [];
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
     * @var string[]
     */
    private $messageConverterReferenceNames = [];
    /**
     * @var AroundInterceptorReference[]
     */
    private $aroundInterceptors = [];
    /**
     * @var object[]
     */
    private $endpointAnnotations = [];

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
    public function withErrorChannel(string $errorChannelName): self
    {
        $this->errorChannelName = $errorChannelName;

        return $this;
    }

    /**
     * @param int $replyMillisecondsTimeout
     * @return GatewayProxyBuilder
     */
    public function withReplyMillisecondTimeout(int $replyMillisecondsTimeout): self
    {
        $this->replyMilliSecondsTimeout = $replyMillisecondsTimeout;

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
    public function getInterfaceName(): string
    {
        return $this->interfaceName;
    }

    /**
     * @param array $methodArgumentConverters
     * @return GatewayProxyBuilder
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function withParameterConverters(array $methodArgumentConverters): self
    {
        Assert::allInstanceOfType($methodArgumentConverters, GatewayParameterConverterBuilder::class);

        $this->methodArgumentConverters = $methodArgumentConverters;

        return $this;
    }

    /**
     * @param string[] $messageConverterReferenceNames
     * @return GatewayProxyBuilder
     */
    public function withMessageConverters(array $messageConverterReferenceNames): self
    {
        $this->messageConverterReferenceNames = $messageConverterReferenceNames;

        return $this;
    }

    /**
     * @param string[] $transactionFactoryReferenceNames
     * @return GatewayProxyBuilder
     */
    public function withTransactionFactories(array $transactionFactoryReferenceNames): self
    {
        $this->transactionFactoryReferenceNames = $transactionFactoryReferenceNames;
        foreach ($transactionFactoryReferenceNames as $transactionFactoryReferenceName) {
            $this->requiredReferenceNames[] = $transactionFactoryReferenceName;
        }

        return $this;
    }

    /**
     * @param AroundInterceptorReference $aroundInterceptorReference
     * @return $this
     */
    public function addAroundInterceptor(AroundInterceptorReference $aroundInterceptorReference)
    {
        $this->aroundInterceptors[] = $aroundInterceptorReference;
        $this->requiredReferenceNames[] = $aroundInterceptorReference->getInterceptorName();

        return $this;
    }

    /**
     * @param object[] $endpointAnnotations
     * @return static
     */
    public function withEndpointAnnotations(iterable $endpointAnnotations)
    {
        $this->endpointAnnotations = $endpointAnnotations;

        return $this;
    }

    /**
     * @return object[]
     */
    public function getEndpointAnnotations(): iterable
    {
        return $this->endpointAnnotations;
    }

    /**
     * @inheritdoc
     */
    public function build(ReferenceSearchService $referenceSearchService, ChannelResolver $channelResolver)
    {
        Assert::isInterface($this->interfaceName, "Gateway should point to interface instead of got {$this->interfaceName}");

        /** @var InterfaceToCallRegistry $interfaceToCallRegistry */
        $interfaceToCallRegistry = $referenceSearchService->get(InterfaceToCallRegistry::REFERENCE_NAME);
        $replyChannel = $this->replyChannelName ? $channelResolver->resolve($this->replyChannelName) : null;
        $requestChannel = $channelResolver->resolve($this->requestChannelName);
        $interfaceToCall = $interfaceToCallRegistry->getFor($this->interfaceName, $this->methodName);

        if (!($interfaceToCall->canItReturnNull() || $interfaceToCall->hasReturnTypeVoid())) {
            /** @var DirectChannel $requestChannel */
            Assert::isSubclassOf($requestChannel, SubscribableChannel::class, "Gateway request channel should not be pollable if expected return type is not nullable");
        }

        if (!$interfaceToCall->canItReturnNull() && $this->errorChannelName && !$interfaceToCall->hasReturnTypeVoid()) {
            throw InvalidArgumentException::create("Gateway {$interfaceToCall} with error channel must allow nullable return type");
        }

        if ($replyChannel) {
            /** @var PollableChannel $replyChannel */
            Assert::isSubclassOf($replyChannel, PollableChannel::class, "Reply channel must be pollable");
        }
        $errorChannel = $this->errorChannelName ? $channelResolver->resolve($this->errorChannelName) : null;

        if (!$interfaceToCall->hasReturnValue() && $this->replyChannelName) {
            throw InvalidArgumentException::create("Can't set reply channel for {$interfaceToCall}");
        }

        $methodArgumentConverters = [];
        foreach ($this->methodArgumentConverters as $messageConverterBuilder) {
            $methodArgumentConverters[] = $messageConverterBuilder->build($referenceSearchService);
        }

        $transactionFactories = [];
        foreach ($this->transactionFactoryReferenceNames as $referenceName) {
            $transactionFactories[] = $referenceSearchService->get($referenceName);
        }
        $messageConverters = [];
        foreach ($this->messageConverterReferenceNames as $messageConverterReferenceName) {
            $messageConverters[] = $referenceSearchService->get($messageConverterReferenceName);
        }

        $gateway = new Gateway(
            $interfaceToCall,
            new MethodCallToMessageConverter(
                $interfaceToCall, $methodArgumentConverters
            ),
            $messageConverters,
            $transactionFactories,
            $requestChannel,
            $replyChannel,
            $errorChannel,
            $this->replyMilliSecondsTimeout,
            $referenceSearchService,
            $this->aroundInterceptors,
            $this->endpointAnnotations
        );

        $factory = new RemoteObjectFactory(new class ($gateway) implements AdapterInterface
        {
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
        return sprintf("Gateway - %s:%s with reference name `%s` for request channel `%s`", $this->interfaceName, $this->methodName, $this->referenceName, $this->requestChannelName);
    }
}