<?php

namespace Ecotone\Modelling;

use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\ClassDefinition;
use Ecotone\Messaging\Handler\Enricher\PropertyEditorAccessor;
use Ecotone\Messaging\Handler\Enricher\PropertyReaderAccessor;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeadersBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadBuilder;
use Ecotone\Messaging\Handler\InputOutputMessageHandlerBuilder;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\MessageHandlerBuilderWithOutputChannel;
use Ecotone\Messaging\Handler\MessageHandlerBuilderWithParameterConverters;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\MessageHandler;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Modelling\Annotation\AggregateEvents;
use Ecotone\Modelling\Annotation\AggregateIdentifier;
use Ecotone\Modelling\Annotation\Version;
use Ecotone\Modelling\Config\BusModule;

/**
 * Class AggregateCallingCommandHandlerBuilder
 * @package Ecotone\Modelling
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class SaveAggregateServiceBuilder extends InputOutputMessageHandlerBuilder implements MessageHandlerBuilderWithParameterConverters, MessageHandlerBuilderWithOutputChannel
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
     * @var string[]
     */
    private array $aggregateRepositoryReferenceNames = [];
    private array $aggregateIdentifierMapping;
    private ?string $aggregateMethodWithEvents;

    private function __construct(ClassDefinition $aggregateClassDefinition, string $methodName)
    {
        $this->initialize($aggregateClassDefinition, $methodName);
    }

    public static function create(ClassDefinition $aggregateClassDefinition, string $methodName): self
    {
        return new self($aggregateClassDefinition, $methodName);
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
        $aggregateRepository = LoadAggregateServiceBuilder::getAggregateRepository($this->interfaceToCall->getInterfaceName(), $this->aggregateRepositoryReferenceNames, $channelResolver, $referenceSearchService);

        $eventBus = GatewayProxyBuilder::create("", EventBus::class, "publish", BusModule::EVENT_CHANNEL_NAME_BY_OBJECT)
            ->withParameterConverters(
                [
                    GatewayPayloadBuilder::create("event"),
                    GatewayHeadersBuilder::create("metadata")
                ]
            )
            ->buildWithoutProxyObject($referenceSearchService, $channelResolver);

        return ServiceActivatorBuilder::createWithDirectReference(
            new SaveAggregateService(
                $this->interfaceToCall,
                $aggregateRepository,
                PropertyEditorAccessor::create($referenceSearchService),
                $this->getPropertyReaderAccessor(),
                $eventBus,
                $this->aggregateMethodWithEvents,
                $this->aggregateIdentifierMapping
            ),
            "save"
        )
            ->withOutputMessageChannel($this->outputMessageChannelName)
            ->build($channelResolver, $referenceSearchService);
    }

    /**
     * @inheritDoc
     */
    public function resolveRelatedInterfaces(InterfaceToCallRegistry $interfaceToCallRegistry): iterable
    {
        return [
            $interfaceToCallRegistry->getFor($this->interfaceToCall->getInterfaceName(), $this->interfaceToCall->getMethodName()),
            $interfaceToCallRegistry->getFor(CallAggregateService::class, "call"),
            $interfaceToCallRegistry->getFor(SaveAggregateService::class, "save")
        ];
    }

    /**
     * @inheritDoc
     */
    public function getInterceptedInterface(InterfaceToCallRegistry $interfaceToCallRegistry): InterfaceToCall
    {
        return $interfaceToCallRegistry->getFor(SaveAggregateService::class, "save");
    }

    public function __toString()
    {
        return sprintf("Aggregate Handler - %s with name `%s` for input channel `%s`", (string)$this->interfaceToCall, $this->getEndpointId(), $this->getInputMessageChannelName());
    }

    private function initialize(ClassDefinition $aggregateClassDefinition, string $methodName): void
    {
        $interfaceToCall = InterfaceToCall::create($aggregateClassDefinition->getClassType()->toString(), $methodName);

        $aggregateMethodWithEvents    = null;

        $aggregateEventsAnnotation = TypeDescriptor::create(AggregateEvents::class);
        foreach ($aggregateClassDefinition->getPublicMethodNames() as $method) {
            $methodToCheck = InterfaceToCall::create($aggregateClassDefinition->getClassType()->toString(), $method);

            if ($methodToCheck->hasMethodAnnotation($aggregateEventsAnnotation)) {
                $aggregateMethodWithEvents = $method;
                break;
            }
        }

        $aggregateIdentifierAnnotation = TypeDescriptor::create(AggregateIdentifier::class);
        $aggregateIdentifiers          = [];
        foreach ($aggregateClassDefinition->getProperties() as $property) {
            if ($property->hasAnnotation($aggregateIdentifierAnnotation)) {
                $aggregateIdentifiers[$property->getName()] = null;
            }
        }

        $this->interfaceToCall            = $interfaceToCall;
        $this->aggregateMethodWithEvents  = $aggregateMethodWithEvents;
        $this->aggregateIdentifierMapping = $aggregateIdentifiers;
    }

    private function getPropertyReaderAccessor(): PropertyReaderAccessor
    {
        return new PropertyReaderAccessor();
    }
}