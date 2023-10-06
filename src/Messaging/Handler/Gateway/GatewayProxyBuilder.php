<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Gateway;

use Ecotone\Messaging\Channel\DirectChannel;
use Ecotone\Messaging\Config\NonProxyCombinedGateway;
use Ecotone\Messaging\Config\ServiceCacheConfiguration;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\Chain\ChainMessageHandlerBuilder;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeadersBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderValueBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadExpressionBuilder;
use Ecotone\Messaging\Handler\InputOutputMessageHandlerBuilder;
use Ecotone\Messaging\Handler\InterceptedEndpoint;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\NonProxyGateway;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\Handler\TypeDefinitionException;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\MessageHandler;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\PollableChannel;
use Ecotone\Messaging\Precedence;
use Ecotone\Messaging\SubscribableChannel;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\InvalidArgumentException;

/**
 * Class GatewayProxySpec
 * @package Ecotone\Messaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class GatewayProxyBuilder implements InterceptedEndpoint
{
    public const DEFAULT_REPLY_MILLISECONDS_TIMEOUT = -1;

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
        $this->requiredReferenceNames[] = ServiceCacheConfiguration::REFERENCE_NAME;
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

    public function withReplyContentType(string $contentType): self
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
     * @return $this
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
     * @return $this
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
    public function addAroundInterceptor(AroundInterceptorReference $aroundInterceptorReference): self
    {
        $this->requiredReferenceNames = array_merge($this->requiredReferenceNames, $aroundInterceptorReference->getRequiredReferenceNames());
        $this->aroundInterceptors[] = $aroundInterceptorReference;
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
    public function addBeforeInterceptor(MethodInterceptor $methodInterceptor): self
    {
        $this->beforeInterceptors[] = $methodInterceptor;

        return $this;
    }

    /**
     * @param MethodInterceptor $methodInterceptor
     * @return $this
     */
    public function addAfterInterceptor(MethodInterceptor $methodInterceptor): self
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
            $interfaceToCallRegistry->getFor(GatewayInternalHandler::class, 'handle'),
            $interfaceToCallRegistry->getFor(ErrorChannelInterceptor::class, 'handle'),
            $interfaceToCallRegistry->getFor(GatewayReplyConverter::class, 'convert'),
            $interfaceToCallRegistry->getFor($this->interfaceName, $this->methodName),
        ];

        foreach ($this->aroundInterceptors as $aroundInterceptor) {
            $resolvedInterfaces[] = $aroundInterceptor->getInterceptingInterface();
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
     * This will be with proxy class, so the resulting object will be implementing interface
     */
    public function build(ReferenceSearchService $referenceSearchService, ChannelResolver $channelResolver): object
    {
        /** @var ServiceCacheConfiguration $serviceCacheConfiguration */
        $serviceCacheConfiguration = $referenceSearchService->get(ServiceCacheConfiguration::REFERENCE_NAME);
        $proxyFactory = ProxyFactory::createWithCache($serviceCacheConfiguration);

        $adapter = new GatewayProxyAdapter(
            NonProxyCombinedGateway::createWith(
                $this->referenceName,
                $this->interfaceName,
                [$this->getRelatedMethodName() => $this],
                $referenceSearchService,
                $channelResolver
            )
        );

        return $proxyFactory->createProxyClassWithAdapter($this->interfaceName, $adapter);
    }

    /**
     * This is used for Framework cases, where framework build their own proxy classes
     */
    public function buildWithoutProxyObject(ReferenceSearchService $referenceSearchService, ChannelResolver $channelResolver): NonProxyGateway
    {
        Assert::isInterface($this->interfaceName, "Gateway should point to interface instead of got {$this->interfaceName} which is not correct interface");

        /** @var InterfaceToCallRegistry $interfaceToCallRegistry */
        $interfaceToCallRegistry = $referenceSearchService->get(InterfaceToCallRegistry::REFERENCE_NAME);
        $replyChannel = $this->replyChannelName ? $channelResolver->resolve($this->replyChannelName) : null;
        $requestChannel = $channelResolver->resolve($this->requestChannelName);
        $interfaceToCall = $interfaceToCallRegistry->getFor($this->interfaceName, $this->methodName);

        if (! ($interfaceToCall->canItReturnNull() || $interfaceToCall->hasReturnTypeVoid())) {
            /** @var DirectChannel $requestChannel */
            Assert::isSubclassOf($requestChannel, SubscribableChannel::class, 'Gateway request channel should not be pollable if expected return type is not nullable');
        }

        if (! $interfaceToCall->canItReturnNull() && $this->errorChannelName && ! $interfaceToCall->hasReturnTypeVoid()) {
            throw InvalidArgumentException::create("Gateway {$interfaceToCall} with error channel must allow nullable return type");
        }

        if ($replyChannel) {
            /** @var PollableChannel $replyChannel */
            Assert::isSubclassOf($replyChannel, PollableChannel::class, 'Reply channel must be pollable');
        }
        $errorChannel = $this->errorChannelName ? $channelResolver->resolve($this->errorChannelName) : null;
        if ($errorChannel) {
            $this->addAroundInterceptor(AroundInterceptorReference::createWithDirectObjectAndResolveConverters(
                $interfaceToCallRegistry,
                new ErrorChannelInterceptor($errorChannel),
                'handle',
                Precedence::ERROR_CHANNEL_PRECEDENCE,
                $this->interfaceName
            ));
        }

        if (! $interfaceToCall->canReturnValue() && $this->replyChannelName) {
            throw InvalidArgumentException::create("Can't set reply channel for {$interfaceToCall}");
        }

        $methodArgumentConverters = [];
        if ($this->replyContentType) {
            $methodArgumentConverters[] = GatewayHeaderValueBuilder::create(MessageHeaders::REPLY_CONTENT_TYPE, $this->replyContentType)->build($referenceSearchService);
        }
        if ($interfaceToCall->hasFirstParameter() && ! $this->hasConverterFor($interfaceToCall->getFirstParameter())) {
            $methodArgumentConverters[] = GatewayPayloadBuilder::create($interfaceToCall->getFirstParameter()->getName())->build($referenceSearchService);
        }
        if ($interfaceToCall->hasSecondParameter() && ! $this->hasConverterFor($interfaceToCall->getSecondParameter())) {
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

        return new Gateway(
            $interfaceToCall,
            new MethodCallToMessageConverter(
                $interfaceToCall,
                $methodArgumentConverters
            ),
            $messageConverters,
            new GatewayReplyConverter(
                $referenceSearchService->get(ConversionService::REFERENCE_NAME),
                $interfaceToCall,
                $messageConverters,
            ),
            $this->buildGatewayInternalHandler($interfaceToCall, $referenceSearchService, $channelResolver)
        );
    }

    private function getRegisteredAnnotations(InterfaceToCall $interfaceToCall): array
    {
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

        return $registeredAnnotations;
    }

    private function buildGatewayInternalHandler(
        InterfaceToCall $interfaceToCall,
        ReferenceSearchService $referenceSearchService,
        ChannelResolver $channelResolver
    ): MessageHandler {
        $registeredAnnotations = $this->getRegisteredAnnotations($interfaceToCall);

        $gatewayInternalHandler = new GatewayInternalHandler(
            $interfaceToCall,
            $channelResolver->resolve($this->requestChannelName),
            $this->replyChannelName ? $channelResolver->resolve($this->replyChannelName) : null,
            $this->replyMilliSecondsTimeout
        );

        $chainHandler = ChainMessageHandlerBuilder::create();
        foreach ($this->getSortedInterceptors($this->beforeInterceptors) as $beforeInterceptor) {
            $chainHandler = $chainHandler->chain($beforeInterceptor);
        }
        $chainHandler = $chainHandler->chainInterceptedHandler(
            ServiceActivatorBuilder::createWithDirectReference($gatewayInternalHandler, 'handle')
                ->withWrappingResultInMessage(false)
                ->withEndpointAnnotations($registeredAnnotations)
        );
        foreach ($this->getSortedInterceptors($this->afterInterceptors) as $afterInterceptor) {
            $chainHandler = $chainHandler->chain($afterInterceptor);
        }

        foreach ($this->getSortedAroundInterceptors($this->aroundInterceptors) as $aroundInterceptorReference) {
            $chainHandler = $chainHandler->addAroundInterceptor($aroundInterceptorReference);
        }

        return $chainHandler
            ->build(
                $channelResolver,
                $referenceSearchService
            );
    }

    /**
     * @return AroundInterceptorReference[]
     */
    private function getSortedAroundInterceptors(array $aroundInterceptors): array
    {
        usort(
            $aroundInterceptors,
            function (AroundInterceptorReference $a, AroundInterceptorReference $b) {
                return $a->getPrecedence() <=> $b->getPrecedence();
            }
        );

        return $aroundInterceptors;
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
        return sprintf('Gateway - %s:%s with reference name `%s` for request channel `%s`', $this->interfaceName, $this->methodName, $this->referenceName, $this->requestChannelName);
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
