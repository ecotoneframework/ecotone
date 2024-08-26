<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Router;

use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\InterfaceToCallReference;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\MessageHandlerBuilderWithParameterConverters;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvokerBuilder;
use Ecotone\Messaging\Support\Assert;

use function get_class;
use function is_string;

/**
 * Class RouterBuilder
 * @package Ecotone\Messaging\Handler\Router
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class RouterBuilder implements MessageHandlerBuilderWithParameterConverters
{
    private ?string $inputMessageChannelName = null;
    /**
     * @var array<ParameterConverterBuilder>
     */
    private array $methodParameterConverters = [];
    private bool $resolutionRequired = true;
    /**
     * @var string[]
     */
    private array $requiredReferenceNames = [];
    private ?string $defaultResolution = null;
    private bool $applySequence = false;
    private ?string $endpointId = '';
    private ?DefinedObject $directObjectToInvoke = null;

    private function __construct(private Reference|Definition $objectToInvokeReference, private string|InterfaceToCall $methodNameOrInterface)
    {
    }

    public static function create(string|Reference|Definition $objectToInvokeReference, InterfaceToCall $interfaceToCall): self
    {
        return new self(
            is_string($objectToInvokeReference) ? Reference::to($objectToInvokeReference) : $objectToInvokeReference,
            $interfaceToCall
        );
    }

    public static function createPayloadTypeRouter(array $typeToChannelMapping): self
    {
        $routerBuilder = new self(new Definition(PayloadTypeRouter::class, [$typeToChannelMapping], 'create'), 'route');

        return $routerBuilder;
    }

    public static function createPayloadTypeRouterByClassName(): self
    {
        $routerBuilder = new self(new Definition(PayloadTypeRouter::class, factory: 'createWithRoutingByClass'), 'route');

        return $routerBuilder;
    }

    public static function createRecipientListRouter(array $recipientLists): self
    {
        $routerBuilder = new self(new Definition(RecipientListRouter::class, [$recipientLists]), 'route');

        return $routerBuilder;
    }

    public static function createHeaderMappingRouter(string $headerName, array $headerValueToChannelMapping): self
    {
        $routerBuilder = new self(HeaderMappingRouter::create($headerName, $headerValueToChannelMapping)->getDefinition(), 'route');

        return $routerBuilder;
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

    public function compile(MessagingContainerBuilder $builder): Definition
    {
        if ($this->methodNameOrInterface instanceof InterfaceToCall) {
            $interfaceToCallReference = InterfaceToCallReference::fromInstance($this->methodNameOrInterface);
        } elseif ($this->directObjectToInvoke) {
            $className = get_class($this->directObjectToInvoke);
            $interfaceToCallReference = new InterfaceToCallReference($className, $this->methodNameOrInterface);
        } else {
            $className = $this->objectToInvokeReference instanceof Definition ? $this->objectToInvokeReference->getClassName() : (string) $this->objectToInvokeReference;
            $interfaceToCallReference = new InterfaceToCallReference($className, $this->methodNameOrInterface);
        }
        $methodInvoker = MethodInvokerBuilder::create(
            $this->directObjectToInvoke ?: $this->objectToInvokeReference,
            $interfaceToCallReference,
            $this->methodParameterConverters,
        )->compileWithoutProcessor($builder);

        return new Definition(Router::class, [
            new Reference(ChannelResolver::class),
            new Definition(InvocationRouter::class, [$methodInvoker]),
            $this->resolutionRequired,
            $this->defaultResolution,
            $this->applySequence,
        ]);
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

    /**
     * @return string
     */
    public function __toString()
    {
        return sprintf('Router for input channel `%s` with name `%s`', $this->inputMessageChannelName, $this->getEndpointId());
    }
}
