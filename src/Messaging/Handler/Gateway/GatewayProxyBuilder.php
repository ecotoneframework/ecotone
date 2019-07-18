<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Gateway;

use ProxyManager\Factory\RemoteObject\AdapterInterface;
use ProxyManager\Factory\RemoteObjectFactory;
use SimplyCodedSoftware\Messaging\Channel\DirectChannel;
use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\InputOutputMessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCallRegistry;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\TypeDescriptor;
use SimplyCodedSoftware\Messaging\MessagingException;
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
     * @var MethodInterceptor[]
     */
    private $beforeInterceptors = [];
    /**
     * @var MethodInterceptor[]
     */
    private $afterInterceptors = [];
    /**
     * @var object[]
     */
    private $endpointAnnotations = [];
    /**
     * @var string[]
     */
    private $requiredInterceptorNames = [];

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
     * @inheritDoc
     */
    public function getRelatedMethodName(): string
    {
        return $this->methodName;
    }

    /**
     * @param array $methodArgumentConverters
     * @return GatewayProxyBuilder
     * @throws MessagingException
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
        foreach ($messageConverterReferenceNames as $messageConverterReferenceName) {
            $this->requiredReferenceNames[] = $messageConverterReferenceName;
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
        $this->requiredReferenceNames = array_merge($this->requiredReferenceNames, $aroundInterceptorReference->getRequiredReferenceNames());

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getInterceptedInterface(InterfaceToCallRegistry $interfaceToCallRegistry): InterfaceToCall
    {
        return $interfaceToCallRegistry->getFor($this->interfaceName, $this->methodName);
    }

    /**
     * @param MethodInterceptor $methodInterceptor
     * @return $this
     */
    public function addBeforeInterceptor(MethodInterceptor $methodInterceptor)
    {
        $this->beforeInterceptors[] = $methodInterceptor;

        return $this;
    }

    /**
     * @param MethodInterceptor $methodInterceptor
     * @return $this
     */
    public function addAfterInterceptor(MethodInterceptor $methodInterceptor)
    {
        $this->afterInterceptors[] = $methodInterceptor;

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
     * @inheritDoc
     */
    public function getRequiredInterceptorNames(): iterable
    {
        return $this->requiredInterceptorNames;
    }

    /**
     * @inheritDoc
     */
    public function withRequiredInterceptorNames(iterable $interceptorNames)
    {
        foreach ($interceptorNames as $interceptorName) {
            $this->requiredInterceptorNames[] = $interceptorName;
        }

        return $this;
    }

    /**
     * @return object[]
     */
    public function getEndpointAnnotations(): array
    {
        return $this->endpointAnnotations;
    }

    /**
     * @inheritdoc
     */
    public function build(ReferenceSearchService $referenceSearchService, ChannelResolver $channelResolver)
    {
        $this->validateInterceptorsCorrectness($referenceSearchService);
        $gateway = $this->buildWithoutProxyObject($referenceSearchService, $channelResolver);

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

    public function buildWithoutProxyObject(ReferenceSearchService $referenceSearchService, ChannelResolver $channelResolver)
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
        $aroundInterceptors = $this->aroundInterceptors;
        $aroundInterceptors[] = AroundInterceptorReference::createWithDirectObject(
            "",
            new ErrorChannelInterceptor($errorChannel),
            "handle",
            ErrorChannelInterceptor::PRECEDENCE,
            ""
        );

        if (!$interfaceToCall->hasReturnValue() && $this->replyChannelName) {
            throw InvalidArgumentException::create("Can't set reply channel for {$interfaceToCall}");
        }

        $methodArgumentConverters = [];
        foreach ($this->methodArgumentConverters as $messageConverterBuilder) {
            $methodArgumentConverters[] = $messageConverterBuilder->build($referenceSearchService);
        }

        $messageConverters = [];
        foreach ($this->messageConverterReferenceNames as $messageConverterReferenceName) {
            $messageConverters[] = $referenceSearchService->get($messageConverterReferenceName);
        }

        $registeredAnnotations = $this->endpointAnnotations;
        foreach ($interfaceToCall->getMethodAnnotations() as $annotation) {
            if ($this->canBeAddedToRegisteredAnnotations($registeredAnnotations, $annotation)) {
                $registeredAnnotations[] = $annotation;
            }
        }
        foreach ($interfaceToCall->getClassAnnotations() as $annotation) {
            if ($this->canBeAddedToRegisteredAnnotations($registeredAnnotations, $annotation)) {
                $registeredAnnotations[] = $annotation;
            }
        }

        return new Gateway(
            $interfaceToCall,
            new MethodCallToMessageConverter(
                $interfaceToCall, $methodArgumentConverters
            ),
            $messageConverters,
            $requestChannel,
            $replyChannel,
            $errorChannel,
            $this->replyMilliSecondsTimeout,
            $referenceSearchService,
            $channelResolver,
            $aroundInterceptors,
            $this->getSortedInterceptors($this->beforeInterceptors),
            $this->getSortedInterceptors($this->afterInterceptors),
            $registeredAnnotations
        );
    }

    /**
     * @param MethodInterceptor[] $methodInterceptors
     * @return InputOutputMessageHandlerBuilder[]
     */
    private function getSortedInterceptors(iterable $methodInterceptors): iterable
    {
        usort($methodInterceptors, function (MethodInterceptor $methodInterceptor, MethodInterceptor $toCompare) {
            if ($methodInterceptor->getPrecedence() === $toCompare->getPrecedence()) {
                return 0;
            }

            return $methodInterceptor->getPrecedence() > $toCompare->getPrecedence() ? 1 : -1;
        });

        return array_map(function (MethodInterceptor $methodInterceptor) {
            return $methodInterceptor->getMessageHandler();
        }, $methodInterceptors);
    }

    public function __toString()
    {
        return sprintf("Gateway - %s:%s with reference name `%s` for request channel `%s`", $this->interfaceName, $this->methodName, $this->referenceName, $this->requestChannelName);
    }

    /**
     * @param array $registeredAnnotations
     * @param object $annotation
     * @return bool
     * @throws MessagingException
     * @throws \SimplyCodedSoftware\Messaging\Handler\TypeDefinitionException
     */
    private function canBeAddedToRegisteredAnnotations(array $registeredAnnotations, object $annotation): bool
    {
        foreach ($registeredAnnotations as $registeredAnnotation) {
            if (TypeDescriptor::createFromVariable($registeredAnnotation)->equals(TypeDescriptor::createFromVariable($annotation))) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param ReferenceSearchService $referenceSearchService
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\Messaging\Handler\ReferenceNotFoundException
     */
    private function validateInterceptorsCorrectness(ReferenceSearchService $referenceSearchService): void
    {
        foreach ($this->aroundInterceptors as $aroundInterceptorReference) {
            $aroundInterceptorReference->buildAroundInterceptor($referenceSearchService);
        }
    }
}