<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Gateway;

use Doctrine\Common\Annotations\AnnotationException;
use Ecotone\Messaging\Channel\DirectChannel;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\InputOutputMessageHandlerBuilder;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor;
use Ecotone\Messaging\Handler\ReferenceNotFoundException;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Handler\TypeDefinitionException;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\PollableChannel;
use Ecotone\Messaging\SubscribableChannel;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\InvalidArgumentException;
use ProxyManager\Configuration;
use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use ProxyManager\Factory\RemoteObject\AdapterInterface;
use ProxyManager\Factory\RemoteObjectFactory;
use ReflectionException;

/**
 * Class GatewayProxySpec
 * @package Ecotone\Messaging\Config
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
     * @var bool
     */
    private $withLazyBuild = false;

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
        $this->requiredReferenceNames[] = ProxyFactory::REFERENCE_NAME;
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
     * @param bool $withLazyBuild
     * @return GatewayProxyBuilder
     */
    public function withLazyBuild(bool $withLazyBuild)
    {
        $this->withLazyBuild = $withLazyBuild;

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
     * @inheritDoc
     */
    public function resolveRelatedInterfaces(InterfaceToCallRegistry $interfaceToCallRegistry): iterable
    {
        return [
            $interfaceToCallRegistry->getFor(GatewayInternalHandler::class, "handle"),
            $interfaceToCallRegistry->getFor(ErrorChannelInterceptor::class, "handle"),
            $interfaceToCallRegistry->getFor(ReplyMessageInterceptor::class, "buildReply")
        ];
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
        /** @var ProxyFactory $proxyFactory */
        $proxyFactory = $referenceSearchService->get(ProxyFactory::REFERENCE_NAME);

        if ($this->withLazyBuild) {
            $buildCallback = function() use ($referenceSearchService, $channelResolver) {
                return $this->buildWithoutProxyObject($referenceSearchService, $channelResolver);
            };
        }else {
            $gateway = $this->buildWithoutProxyObject($referenceSearchService, $channelResolver);
            $buildCallback = function() use ($gateway) {
                return $gateway;
            };
        }

        return $proxyFactory->createProxyClass($this->interfaceName, $buildCallback);
    }

    public function buildWithoutProxyObject(ReferenceSearchService $referenceSearchService, ChannelResolver $channelResolver)
    {
        $this->validateInterceptorsCorrectness($referenceSearchService);
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
        $aroundInterceptors[] = AroundInterceptorReference::createWithDirectObject(
            "",
            new ReplyMessageInterceptor(),
            "buildReply",
            ErrorChannelInterceptor::PRECEDENCE + 1,
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
     * @param ReferenceSearchService $referenceSearchService
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws AnnotationException
     * @throws ReflectionException
     * @throws ReferenceNotFoundException
     */
    private function validateInterceptorsCorrectness(ReferenceSearchService $referenceSearchService): void
    {
        foreach ($this->aroundInterceptors as $aroundInterceptorReference) {
            $aroundInterceptorReference->buildAroundInterceptor($referenceSearchService);
        }
    }

    /**
     * @param array $registeredAnnotations
     * @param object $annotation
     * @return bool
     * @throws MessagingException
     * @throws TypeDefinitionException
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
}