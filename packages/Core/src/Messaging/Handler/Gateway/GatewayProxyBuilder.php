<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Gateway;

use Doctrine\Common\Annotations\AnnotationException;
use Ecotone\Messaging\Channel\DirectChannel;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeadersBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderValueBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadExpressionBuilder;
use Ecotone\Messaging\Handler\InputOutputMessageHandlerBuilder;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\NonProxyGateway;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor;
use Ecotone\Messaging\Handler\ReferenceNotFoundException;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\Handler\TypeDefinitionException;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\PollableChannel;
use Ecotone\Messaging\Precedence;
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

    private string $referenceName;
    private string $interfaceName;
    private string $methodName;
    private string $requestChannelName;
    private int $replyMilliSecondsTimeout = self::DEFAULT_REPLY_MILLISECONDS_TIMEOUT;
    private ?string $replyChannelName = null;
    private ?string $replyContentType = null;
    /**
     * @var GatewayParameterConverterBuilder[]
     */
    private array $methodArgumentConverters = [];
    private ?string $errorChannelName = null;
    /**
     * @var string[]
     */
    private array $requiredReferenceNames = [];
    /**
     * @var string[]
     */
    private array $messageConverterReferenceNames = [];
    /**
     * @var AroundInterceptorReference[]
     */
    private array $aroundInterceptors = [];
    /**
     * @var MethodInterceptor[]
     */
    private array $beforeInterceptors = [];
    /**
     * @var MethodInterceptor[]
     */
    private array $afterInterceptors = [];
    /**
     * @var object[]
     */
    private iterable $endpointAnnotations = [];
    /**
     * @var string[]
     */
    private array $requiredInterceptorNames = [];
    private bool $withLazyBuild = false;

    /**
     * GatewayProxyBuilder constructor.
     * @param string $referenceName
     * @param string $interfaceName
     * @param string $methodName
     * @param string $requestChannelName
     */
    private function __construct(string $referenceName, string $interfaceName, string $methodName, string $requestChannelName)
    {
        Assert::notNullAndEmpty($requestChannelName, "Request channel for {$interfaceName}:{$methodName} can not be empty.");

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

    public function withReplyContentType(string $contentType) : self
    {
        $this->replyContentType = MediaType::parseMediaType($contentType)->toString();

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
    public function withLazyBuild(bool $withLazyBuild): \Ecotone\Messaging\Handler\Gateway\GatewayBuilder
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
     * @param GatewayParameterConverterBuilder[] $methodArgumentConverters
     * @return GatewayProxyBuilder
     * @throws MessagingException
     */
    public function withParameterConverters(array $methodArgumentConverters): self
    {
        Assert::allInstanceOfType($methodArgumentConverters, GatewayParameterConverterBuilder::class);
        $amount = 0;
        foreach ($methodArgumentConverters as $methodArgumentConverter) {
            $amount += $methodArgumentConverter instanceof GatewayPayloadBuilder || $methodArgumentConverter instanceof GatewayPayloadExpressionBuilder;
        }
        Assert::isTrue($amount <= 1, "Can't create gateway {$this} with two Payload converters");

        $this->methodArgumentConverters = $methodArgumentConverters;

        return $this;
    }

    /**
     * @param string[] $messageConverterReferenceNames
     * @return GatewayProxyBuilder
     */
    public function withMessageConverters(array $messageConverterReferenceNames): \Ecotone\Messaging\Handler\Gateway\GatewayBuilder
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
    public function addAroundInterceptor(AroundInterceptorReference $aroundInterceptorReference): self
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
    public function addBeforeInterceptor(MethodInterceptor $methodInterceptor): \Ecotone\Messaging\Handler\Gateway\GatewayBuilder
    {
        $this->beforeInterceptors[] = $methodInterceptor;

        return $this;
    }

    /**
     * @param MethodInterceptor $methodInterceptor
     * @return $this
     */
    public function addAfterInterceptor(MethodInterceptor $methodInterceptor): \Ecotone\Messaging\Handler\Gateway\GatewayBuilder
    {
        $this->afterInterceptors[] = $methodInterceptor;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function resolveRelatedInterfaces(InterfaceToCallRegistry $interfaceToCallRegistry): iterable
    {
        $resolvedInterfaces = [
            $interfaceToCallRegistry->getFor(GatewayInternalHandler::class, "handle"),
            $interfaceToCallRegistry->getFor(ErrorChannelInterceptor::class, "handle"),
            $interfaceToCallRegistry->getFor(ConversionInterceptor::class, "convert"),
            $interfaceToCallRegistry->getFor($this->interfaceName, $this->methodName)
        ];

        foreach ($this->aroundInterceptors as $aroundInterceptor) {
            $resolvedInterfaces[] = $aroundInterceptor->getInterceptingInterface($interfaceToCallRegistry);
        }

        return $resolvedInterfaces;
    }

    /**
     * @param object[] $endpointAnnotations
     * @return static
     */
    public function withEndpointAnnotations(iterable $endpointAnnotations): self
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
    public function withRequiredInterceptorNames(iterable $interceptorNames): self
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
    public function build(ReferenceSearchService $referenceSearchService, ChannelResolver $channelResolver): object
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

    public function buildWithoutProxyObject(ReferenceSearchService $referenceSearchService, ChannelResolver $channelResolver) : NonProxyGateway
    {
        $this->validateInterceptorsCorrectness($referenceSearchService);
        Assert::isInterface($this->interfaceName, "Gateway should point to interface instead of got {$this->interfaceName} which is not correct interface");

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
        if ($errorChannel) {
            $aroundInterceptors[] = AroundInterceptorReference::createWithDirectObjectAndResolveConverters(
                new ErrorChannelInterceptor($errorChannel),
                "handle",
                Precedence::ERROR_CHANNEL_PRECEDENCE,
                $this->interfaceName
            );
        }

        if (!$interfaceToCall->canReturnValue() && $this->replyChannelName) {
            throw InvalidArgumentException::create("Can't set reply channel for {$interfaceToCall}");
        }

        $methodArgumentConverters = [];
        if ($this->replyContentType) {
            $methodArgumentConverters[] = GatewayHeaderValueBuilder::create(MessageHeaders::REPLY_CONTENT_TYPE, $this->replyContentType)->build($referenceSearchService);
        }
        if ($interfaceToCall->hasFirstParameter() && !$this->hasConverterFor($interfaceToCall->getFirstParameter())) {
            $methodArgumentConverters[] = GatewayPayloadBuilder::create($interfaceToCall->getFirstParameter()->getName())->build($referenceSearchService);
        }
        if ($interfaceToCall->hasSecondParameter() && !$this->hasConverterFor($interfaceToCall->getSecondParameter())) {
            if ($interfaceToCall->getSecondParameter()->getTypeDescriptor()->isNonCollectionArray()) {
                $methodArgumentConverters[] = GatewayHeadersBuilder::create($interfaceToCall->getSecondParameter()->getName())->build($referenceSearchService);
            }
        }

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

        $beforeInterceptors = $this->beforeInterceptors;
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
            $this->getSortedInterceptors($beforeInterceptors),
            $this->getSortedInterceptors($this->afterInterceptors),
            $registeredAnnotations
        );
    }

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

    private function hasConverterFor(\Ecotone\Messaging\Handler\InterfaceParameter $parameter): bool
    {
        foreach ($this->methodArgumentConverters as $parameterConverter) {
            if ($parameterConverter->isHandling($parameter)) {
                return true;
            }
        }

        return false;
    }
}