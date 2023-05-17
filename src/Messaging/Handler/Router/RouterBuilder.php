<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Router;

use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\MessageHandlerBuilderWithParameterConverters;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvoker;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\MessageHandler;
use Ecotone\Messaging\Support\Assert;

/**
 * Class RouterBuilder
 * @package Ecotone\Messaging\Handler\Router
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class RouterBuilder implements MessageHandlerBuilderWithParameterConverters
{
    private ?string $inputMessageChannelName = null;
    private string $objectToInvokeReference;
    private ?object $directObjectToInvoke = null;
    private array $methodParameterConverters = [];
    private bool $resolutionRequired = true;
    /**
     * @var string[]
     */
    private array $requiredReferenceNames = [];
    private ?string $defaultResolution = null;
    private bool $applySequence = false;
    private ?string $endpointId = '';

    private function __construct(string $objectToInvokeReference, private string|InterfaceToCall $methodNameOrInterface)
    {
        $this->objectToInvokeReference = $objectToInvokeReference;

        if ($objectToInvokeReference) {
            $this->requiredReferenceNames[] = $objectToInvokeReference;
        }
    }

    public static function create(string $objectToInvokeReference, InterfaceToCall $interfaceToCall): self
    {
        return new self($objectToInvokeReference, $interfaceToCall);
    }

    public static function createPayloadTypeRouter(array $typeToChannelMapping): self
    {
        $routerBuilder = new self('', 'route');
        $routerBuilder->setObjectToInvoke(PayloadTypeRouter::create($typeToChannelMapping));

        return $routerBuilder;
    }

    /**
     * @inheritDoc
     */
    public function resolveRelatedInterfaces(InterfaceToCallRegistry $interfaceToCallRegistry): iterable
    {
        return [$this->methodNameOrInterface instanceof InterfaceToCall
                ? $this->methodNameOrInterface
                : $interfaceToCallRegistry->getFor($this->directObjectToInvoke, $this->getMethodName())];
    }

    public static function createRouterFromObject(object $customRouterObject, string $methodName): self
    {
        $routerBuilder = new self('', $methodName);
        $routerBuilder->setObjectToInvoke($customRouterObject);

        return $routerBuilder;
    }

    public static function createPayloadTypeRouterByClassName(): self
    {
        $routerBuilder = new self('', 'route');
        $routerBuilder->setObjectToInvoke(PayloadTypeRouter::createWithRoutingByClass());

        return $routerBuilder;
    }

    public static function createRecipientListRouter(array $recipientLists): self
    {
        $routerBuilder = new self('', 'route');
        $routerBuilder->setObjectToInvoke(new RecipientListRouter($recipientLists));

        return $routerBuilder;
    }

    public static function createHeaderMappingRouter(string $headerName, array $headerValueToChannelMapping): self
    {
        $routerBuilder = new self('', 'route');
        $routerBuilder->setObjectToInvoke(HeaderMappingRouter::create($headerName, $headerValueToChannelMapping));

        return $routerBuilder;
    }

    public static function createHeaderRouter(string $headerName): self
    {
        $routerBuilder = new self('', 'route');
        $routerBuilder->setObjectToInvoke(HeaderRouter::create($headerName));

        return $routerBuilder;
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferenceNames(): array
    {
        $requiredReferenceNames = $this->requiredReferenceNames;
        $requiredReferenceNames[] = $this->objectToInvokeReference;

        return $requiredReferenceNames;
    }

    /**
     * @inheritDoc
     */
    public function withMethodParameterConverters(array $methodParameterConverterBuilders): self
    {
        Assert::allInstanceOfType($methodParameterConverterBuilders, ParameterConverterBuilder::class);

        $this->methodParameterConverters = $methodParameterConverterBuilders;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getParameterConverters(): array
    {
        return $this->methodParameterConverters;
    }

    /**
     * @inheritDoc
     */
    public function withInputChannelName(string $inputChannelName): self
    {
        $self = clone $this;
        $self->inputMessageChannelName = $inputChannelName;

        return $self;
    }

    /**
     * @param string $channelName
     * @return RouterBuilder
     */
    public function withDefaultResolutionChannel(string $channelName): self
    {
        $this->defaultResolution = $channelName;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService): MessageHandler
    {
        $objectToInvoke = $this->directObjectToInvoke ? $this->directObjectToInvoke : $referenceSearchService->get($this->objectToInvokeReference);
        /** @var InterfaceToCallRegistry $interfaceToCallRegistry */
        $interfaceToCallRegistry = $referenceSearchService->get(InterfaceToCallRegistry::REFERENCE_NAME);

        return Router::create(
            $channelResolver,
            MethodInvoker::createWith($interfaceToCallRegistry->getFor($objectToInvoke, $this->getMethodName()), $objectToInvoke, $this->methodParameterConverters, $referenceSearchService),
            $this->resolutionRequired,
            $this->defaultResolution,
            $this->applySequence
        );
    }

    /**
     * @param bool $resolutionRequired
     * @return RouterBuilder
     */
    public function setResolutionRequired(bool $resolutionRequired): self
    {
        $this->resolutionRequired = $resolutionRequired;

        return $this;
    }

    /**
     * @param bool $applySequence
     *
     * @return RouterBuilder
     */
    public function withApplySequence(bool $applySequence): self
    {
        $this->applySequence = $applySequence;

        return $this;
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
    public function getEndpointId(): ?string
    {
        return $this->endpointId;
    }

    /**
     * @inheritDoc
     */
    public function withEndpointId(string $endpointId): self
    {
        $this->endpointId = $endpointId;

        return $this;
    }

    private function setObjectToInvoke(object $objectToInvoke): void
    {
        $this->directObjectToInvoke = $objectToInvoke;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return sprintf('Router for input channel `%s` with name `%s`', $this->inputMessageChannelName, $this->getEndpointId());
    }

    private function getMethodName(): string
    {
        return $this->methodNameOrInterface instanceof InterfaceToCall ? $this->methodNameOrInterface->getMethodName() : $this->methodNameOrInterface;
    }
}
