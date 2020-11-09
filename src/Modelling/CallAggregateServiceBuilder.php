<?php

namespace Ecotone\Modelling;

use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\ClassDefinition;
use Ecotone\Messaging\Handler\InputOutputMessageHandlerBuilder;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\MessageHandlerBuilderWithOutputChannel;
use Ecotone\Messaging\Handler\MessageHandlerBuilderWithParameterConverters;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\Handler\Transformer\TransformerBuilder;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\MessageHandler;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Modelling\Annotation\AggregateFactory;

class CallAggregateServiceBuilder extends InputOutputMessageHandlerBuilder implements MessageHandlerBuilderWithParameterConverters, MessageHandlerBuilderWithOutputChannel
{
    private InterfaceToCall $interfaceToCall;
    /**
     * @var ParameterConverterBuilder[]
     */
    private array $methodParameterConverterBuilders = [];
    /**
     * @var string[]
     */
    private array $requiredReferences = [];
    /**
     * @var bool
     */
    private bool $isCommandHandler;
    /**
     * @var string[]
     */
    private array $aggregateRepositoryReferenceNames = [];
    private bool $isVoidMethod;
    private ?string $eventSourcedFactoryMethod;

    private function __construct(ClassDefinition $aggregateClassDefinition, string $methodName, bool $isCommandHandler)
    {
        $this->isCommandHandler = $isCommandHandler;

        $this->initialize($aggregateClassDefinition, $methodName);
    }

    private function initialize(ClassDefinition $aggregateClassDefinition, string $methodName): void
    {
        $interfaceToCall = InterfaceToCall::create($aggregateClassDefinition->getClassType()->toString(), $methodName);
        $this->isVoidMethod = $interfaceToCall->getReturnType()->isVoid();

        $eventSourcedFactoryMethod = null;
        $aggregateFactoryAnnotation = TypeDescriptor::create(AggregateFactory::class);
        foreach ($aggregateClassDefinition->getPublicMethodNames() as $method) {
            $methodToCheck = InterfaceToCall::create($aggregateClassDefinition->getClassType()->toString(), $method);

            if ($methodToCheck->hasMethodAnnotation($aggregateFactoryAnnotation)) {
                $eventSourcedFactoryMethod = $method;
                break;
            }
        }

        $this->interfaceToCall = $interfaceToCall;
        $this->eventSourcedFactoryMethod = $eventSourcedFactoryMethod;
    }

    public static function create(ClassDefinition $aggregateClassDefinition, string $methodName, bool $isCommandHandler): self
    {
        return new self($aggregateClassDefinition, $methodName, $isCommandHandler);
    }

    /**
     * @inheritDoc
     */
    public function getParameterConverters(): array
    {
        return $this->methodParameterConverterBuilders;
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferenceNames(): array
    {
        return $this->requiredReferences;
    }

    /**
     * @inheritDoc
     */
    public function withMethodParameterConverters(array $methodParameterConverterBuilders): self
    {
        Assert::allInstanceOfType($methodParameterConverterBuilders, ParameterConverterBuilder::class);

        $this->methodParameterConverterBuilders = $methodParameterConverterBuilders;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService): MessageHandler
    {
        $aggregateRepository = $this->getAggregateRepository($referenceSearchService);
        $isEventSourced = $aggregateRepository instanceof EventSourcedRepository;
        $isFactoryMethod = $this->interfaceToCall->isStaticallyCalled();

        $handler = ServiceActivatorBuilder::createWithDirectReference(
            new CallAggregateService($this->interfaceToCall, $isEventSourced, $channelResolver, $this->methodParameterConverterBuilders, $this->orderedAroundInterceptors, $referenceSearchService, $this->isCommandHandler, $isFactoryMethod, $this->eventSourcedFactoryMethod),
            "call"
        )
            ->withPassThroughMessageOnVoidInterface($this->isVoidMethod)
            ->withOutputMessageChannel($this->outputMessageChannelName);

        return $handler->build($channelResolver, $referenceSearchService);
    }

    private function getAggregateRepository(ReferenceSearchService $referenceSearchService): ?object
    {
        $aggregateRepository = null;
        foreach ($this->aggregateRepositoryReferenceNames as $aggregateRepositoryName) {
            /** @var StandardRepository|EventSourcedRepository $aggregateRepository */
            $aggregateRepositoryToCheck = $referenceSearchService->get($aggregateRepositoryName);
            if ($aggregateRepositoryToCheck->canHandle($this->interfaceToCall->getInterfaceName())) {
                $aggregateRepository = $aggregateRepositoryToCheck;
                break;
            }
        }
        Assert::notNull($aggregateRepository, "Aggregate Repository not found for {$this->interfaceToCall}");
        return $aggregateRepository;
    }

    /**
     * @param string[] $aggregateRepositoryReferenceNames
     */
    public function withAggregateRepositoryFactories(array $aggregateRepositoryReferenceNames): self
    {
        $this->aggregateRepositoryReferenceNames = $aggregateRepositoryReferenceNames;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function resolveRelatedInterfaces(InterfaceToCallRegistry $interfaceToCallRegistry): iterable
    {
        return [
            $interfaceToCallRegistry->getFor($this->interfaceToCall->getInterfaceName(), $this->interfaceToCall->getMethodName()),
            $interfaceToCallRegistry->getFor(CallAggregateService::class, "call")
        ];
    }

    /**
     * @inheritDoc
     */
    public function getInterceptedInterface(InterfaceToCallRegistry $interfaceToCallRegistry): InterfaceToCall
    {
        return $interfaceToCallRegistry->getFor($this->interfaceToCall->getInterfaceName(), $this->interfaceToCall->getMethodName());
    }

    public function __toString()
    {
        return sprintf("Aggregate Handler - %s with name `%s` for input channel `%s`", (string)$this->interfaceToCall, $this->getEndpointId(), $this->getInputMessageChannelName());
    }
}