<?php

namespace Ecotone\Modelling;

use Doctrine\Common\Annotations\AnnotationException;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Handler\Chain\ChainMessageHandlerBuilder;
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
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use Ecotone\Messaging\Handler\ReferenceNotFoundException;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\MessageHandler;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Modelling\Annotation\AggregateEvents;
use Ecotone\Modelling\Annotation\AggregateFactory;
use Ecotone\Modelling\Annotation\AggregateIdentifier;
use Ecotone\Modelling\Annotation\TargetAggregateIdentifier;
use Ecotone\Modelling\Annotation\TargetAggregateVersion;
use Ecotone\Modelling\Annotation\Version;
use Ecotone\Modelling\LazyEventBus\LazyEventBus;
use ReflectionException;

/**
 * Class AggregateCallingCommandHandlerBuilder
 * @package Ecotone\Modelling
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AggregateMessageHandlerBuilder extends InputOutputMessageHandlerBuilder implements MessageHandlerBuilderWithParameterConverters, MessageHandlerBuilderWithOutputChannel
{
    private string $aggregateClassName;
    private string $methodName;
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
    private ?array $versionMapping;
    private array $aggregateIdentifierMapping;
    private ?string $aggregateMethodWithEvents;
    private ?string $eventSourcedFactoryMethod;

    private function __construct(ClassDefinition $aggregateClassDefinition, string $methodName, bool $isCommandHandler)
    {
        $this->aggregateClassName = $aggregateClassDefinition->getClassType()->toString();
        $this->methodName = $methodName;
        $this->isCommandHandler = $isCommandHandler;

        $this->initialize($aggregateClassDefinition);
    }

    private function initialize(ClassDefinition $aggregateClassDefinition): void
    {
        $interfaceToCall = InterfaceToCall::create($this->aggregateClassName, $this->methodName);
        $this->isVoidMethod = $interfaceToCall->getReturnType()->isVoid();

        $aggregateMethodWithEvents = null;
        $aggregateVersionPropertyName = null;
        $eventSourcedFactoryMethod = null;

        $aggregateFactoryAnnotation = TypeDescriptor::create(AggregateFactory::class);
        $aggregateEventsAnnotation = TypeDescriptor::create(AggregateEvents::class);
        foreach ($aggregateClassDefinition->getPublicMethodNames() as $method) {
            $methodToCheck = InterfaceToCall::create($aggregateClassDefinition->getClassType()->toString(), $method);

            if ($methodToCheck->hasMethodAnnotation($aggregateFactoryAnnotation)) {
                $eventSourcedFactoryMethod = $method;
                break;
            }
            if ($methodToCheck->hasMethodAnnotation($aggregateEventsAnnotation)) {
                $aggregateMethodWithEvents = $method;
                break;
            }
        }

        $aggregateIdentifierAnnotation = TypeDescriptor::create(AggregateIdentifier::class);
        $versionAnnotation = TypeDescriptor::create(Version::class);
        $aggregateIdentifiers = [];
        foreach ($aggregateClassDefinition->getProperties() as $property) {
            if ($property->hasAnnotation($versionAnnotation)) {
                $aggregateVersionPropertyName = $property->getName();
            }
            if ($property->hasAnnotation($aggregateIdentifierAnnotation)) {
                $aggregateIdentifiers[$property->getName()] = null;
            }
        }

        $aggregateVersionMapping = null;
        if (!$aggregateVersionMapping && $aggregateVersionPropertyName) {
            $aggregateVersionMapping[$aggregateVersionPropertyName] = $aggregateVersionPropertyName;
        }

        $this->versionMapping           = $aggregateVersionMapping;
        $this->interfaceToCall = $interfaceToCall;
        $this->aggregateMethodWithEvents = $aggregateMethodWithEvents;
        $this->eventSourcedFactoryMethod = $eventSourcedFactoryMethod;
        $this->aggregateIdentifierMapping = $aggregateIdentifiers;
    }

    public static function createAggregateCommandHandlerWith(ClassDefinition $aggregateClassDefinition, string $methodName): self
    {
        return new self($aggregateClassDefinition, $methodName, true);
    }

    public static function createAggregateQueryHandlerWith(ClassDefinition $aggregateClassDefinition, string $methodName): self
    {
        return new self($aggregateClassDefinition, $methodName, false);
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
     * @return AggregateMessageHandlerBuilder
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
        $chainCqrsMessageHandler = ChainMessageHandlerBuilder::create();
        $aggregateRepository = $this->getAggregateRepository($referenceSearchService);
        $isEventSourced = $aggregateRepository instanceof EventSourcedRepository;

        if ($isEventSourced && !$this->eventSourcedFactoryMethod) {
            $repositoryClass = get_class($aggregateRepository);
            throw InvalidArgumentException::create("Based on your repository {$repositoryClass}, you want to create Event Sourced Aggregate. You must define static method marked with @AggregateFactory for aggregate recreation from events");
        }

        $chainCqrsMessageHandler
            ->chain(
                ServiceActivatorBuilder::createWithDirectReference(
                    new CallAggregateService($this->interfaceToCall->getInterfaceName(), $this->interfaceToCall->getMethodName(), $isEventSourced, $channelResolver, $this->methodParameterConverterBuilders, $this->orderedAroundInterceptors, $referenceSearchService, $this->eventSourcedFactoryMethod, $this->isCommandHandler),
                    "call"
                )->withPassThroughMessageOnVoidInterface($this->isVoidMethod)
            );

        $lazyEventBus = GatewayProxyBuilder::create("", LazyEventBus::class, "sendWithMetadata", LazyEventBus::CHANNEL_NAME)
            ->withParameterConverters([
                GatewayPayloadBuilder::create("event"),
                GatewayHeadersBuilder::create("metadata")
            ])
            ->buildWithoutProxyObject($referenceSearchService, $channelResolver);
        if ($this->isCommandHandler) {
            $chainCqrsMessageHandler
                ->chain(
                    ServiceActivatorBuilder::createWithDirectReference(
                        new SaveAggregateService(
                            $this->aggregateClassName,
                            $this->methodName,
                            $this->interfaceToCall->isStaticallyCalled(),
                            $aggregateRepository,
                            PropertyEditorAccessor::create($referenceSearchService),
                            $this->getPropertyReaderAccessor(),
                            $lazyEventBus,
                            $this->aggregateMethodWithEvents,
                            $this->versionMapping,
                            $this->aggregateIdentifierMapping
                        ),
                        "save"
                    )
                );
        }

        return $chainCqrsMessageHandler
            ->withOutputMessageChannel($this->outputMessageChannelName)
            ->build($channelResolver, $referenceSearchService);
    }

    private function getAggregateRepository(ReferenceSearchService $referenceSearchService): object
    {
        $aggregateRepository = null;
        foreach ($this->aggregateRepositoryReferenceNames as $aggregateRepositoryName) {
            /** @var StandardRepository|EventSourcedRepository $aggregateRepository */
            $aggregateRepositoryToCheck = $referenceSearchService->get($aggregateRepositoryName);
            if ($aggregateRepositoryToCheck->canHandle($this->aggregateClassName)) {
                $aggregateRepository = $aggregateRepositoryToCheck;
                break;
            }
        }
        Assert::notNull($aggregateRepository, "Aggregate Repository not found for {$this->aggregateClassName}:{$this->methodName}");
        return $aggregateRepository;
    }

    private function getPropertyReaderAccessor(): PropertyReaderAccessor
    {
        return new PropertyReaderAccessor();
    }

    /**
     * @inheritDoc
     */
    public function resolveRelatedInterfaces(InterfaceToCallRegistry $interfaceToCallRegistry): iterable
    {
        return [
            $interfaceToCallRegistry->getFor($this->aggregateClassName, $this->methodName),
            $interfaceToCallRegistry->getFor(CallAggregateService::class, "call"),
            $interfaceToCallRegistry->getFor(SaveAggregateService::class, "save")
        ];
    }

    /**
     * @inheritDoc
     */
    public function getInterceptedInterface(InterfaceToCallRegistry $interfaceToCallRegistry): InterfaceToCall
    {
        return $interfaceToCallRegistry->getFor($this->aggregateClassName, $this->methodName);
    }

    public function __toString()
    {
        return sprintf("Aggregate Handler - %s:%s with name `%s` for input channel `%s`", $this->aggregateClassName, $this->methodName, $this->getEndpointId(), $this->getInputMessageChannelName());
    }

    private function hasIdentifierMappingInMetadata(array $metadataIdentifierMapping, $aggregateIdentifierName): bool
    {
        foreach ($metadataIdentifierMapping as $identifierNameHeaderMapping => $headerName) {
            if ($aggregateIdentifierName == $identifierNameHeaderMapping) {
                return true;
            }
        }

        return false;
    }
}