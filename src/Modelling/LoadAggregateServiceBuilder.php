<?php


namespace Ecotone\Modelling;


use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\ClassDefinition;
use Ecotone\Messaging\Handler\Enricher\PropertyReaderAccessor;
use Ecotone\Messaging\Handler\InputOutputMessageHandlerBuilder;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\MessageHandler;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Modelling\Attribute\AggregateEvents;
use Ecotone\Modelling\Attribute\AggregateFactory;
use Ecotone\Modelling\Attribute\EventSourcedAggregate;
use Ecotone\Modelling\Attribute\TargetAggregateVersion;

class LoadAggregateServiceBuilder extends InputOutputMessageHandlerBuilder
{
    private string $aggregateClassName;
    private string $methodName;
    private ?string $aggregateMessageVersionPropertyName;
    private array $aggregateRepositoryReferenceNames;
    private ?string $handledMessageClassName;
    private ?string $eventSourcedFactoryMethod;
    private LoadAggregateMode $loadAggregateMode;
    private bool $isEventSourced;

    private function __construct(ClassDefinition $aggregateClassName, string $methodName, ?ClassDefinition $handledMessageClass, LoadAggregateMode $loadAggregateMode)
    {
        $this->aggregateClassName      = $aggregateClassName;
        $this->methodName              = $methodName;
        $this->handledMessageClassName = $handledMessageClass;
        $this->loadAggregateMode = $loadAggregateMode;

        $this->initialize($aggregateClassName, $handledMessageClass);
    }

    public static function create(ClassDefinition $aggregateClassDefinition, string $methodName, ?ClassDefinition $handledMessageClass, LoadAggregateMode $loadAggregateMode): self
    {
        return new self($aggregateClassDefinition, $methodName, $handledMessageClass, $loadAggregateMode);
    }

    public function getInterceptedInterface(InterfaceToCallRegistry $interfaceToCallRegistry): InterfaceToCall
    {
        return $interfaceToCallRegistry->getFor($this->aggregateClassName, $this->methodName);
    }

    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService): MessageHandler
    {
        if ($this->isEventSourced && !$this->eventSourcedFactoryMethod) {
            throw InvalidArgumentException::create("Your aggregate {$this->aggregateClassName}, is event sourced. You must define static method with attribute " . AggregateFactory::class . " for aggregate recreation from events");
        }
        $aggregateRepository = $this->isEventSourced
            ? LazyEventSourcedRepository::create(
                $this->aggregateClassName,
                $this->isEventSourced,
                $channelResolver,
                $referenceSearchService,
                $this->aggregateRepositoryReferenceNames
            ) : LazyStandardRepository::create(
                $this->aggregateClassName,
                $this->isEventSourced,
                $channelResolver,
                $referenceSearchService,
                $this->aggregateRepositoryReferenceNames
            );

        return ServiceActivatorBuilder::createWithDirectReference(
            new LoadAggregateService(
                $aggregateRepository,
                $this->aggregateClassName,
                $this->isEventSourced,
                $this->methodName,
                $this->aggregateMessageVersionPropertyName,
                new PropertyReaderAccessor(),
                $this->eventSourcedFactoryMethod,
                $this->loadAggregateMode
            ),
            "load"
        )
            ->withOutputMessageChannel($this->getOutputMessageChannelName())
            ->build($channelResolver, $referenceSearchService);
    }

    /**
     * @param string[] $aggregateRepositoryReferenceNames
     */
    public function withAggregateRepositoryFactories(array $aggregateRepositoryReferenceNames): self
    {
        $this->aggregateRepositoryReferenceNames = $aggregateRepositoryReferenceNames;

        return $this;
    }

    public function resolveRelatedInterfaces(InterfaceToCallRegistry $interfaceToCallRegistry): iterable
    {
        return [
            $interfaceToCallRegistry->getFor(LoadAggregateService::class, "load")
        ];
    }

    public function getRequiredReferenceNames(): array
    {
        return [];
    }

    private function initialize(ClassDefinition $aggregateClassDefinition, ?ClassDefinition $handledMessageClassName): void
    {
        $aggregateMethodWithEvents          = null;
        $aggregateMessageVersionPropertyName       = null;

        $aggregateFactoryAnnotation = TypeDescriptor::create(AggregateFactory::class);
        foreach ($aggregateClassDefinition->getPublicMethodNames() as $method) {
            $methodToCheck = InterfaceToCall::create($aggregateClassDefinition->getClassType()->toString(), $method);

            if ($methodToCheck->hasMethodAnnotation($aggregateFactoryAnnotation)) {
                $factoryMethodInterface = InterfaceToCall::create($aggregateClassDefinition->getClassType()->toString(), $method);
                Assert::isTrue($factoryMethodInterface->hasSingleParameter(), "Event sourced factory method {$aggregateClassDefinition}:{$method} should contain only one iterable parameter");
                Assert::isTrue($factoryMethodInterface->isStaticallyCalled(), "Event sourced factory method {$aggregateClassDefinition}:{$method} should be static");
                Assert::isTrue($factoryMethodInterface->getFirstParameter()->getTypeDescriptor()->isIterable(), "Event sourced factory method {$aggregateClassDefinition}:{$method} should type hint for array or iterable");

                break;
            }
        }

        $this->isEventSourced = $aggregateClassDefinition->hasClassAnnotation(TypeDescriptor::create(EventSourcedAggregate::class));
        $aggregateMessageVersionPropertyName = null;
        if ($handledMessageClassName) {
            $targetAggregateVersion            = TypeDescriptor::create(TargetAggregateVersion::class);
            foreach ($handledMessageClassName->getProperties() as $property) {
                if ($property->hasAnnotation($targetAggregateVersion)) {
                    $aggregateMessageVersionPropertyName = $property->getName();
                }
            }
        }
        $eventSourcedFactoryMethod = null;
        $aggregateFactoryAnnotation = TypeDescriptor::create(AggregateFactory::class);
        foreach ($aggregateClassDefinition->getPublicMethodNames() as $method) {
            $methodToCheck = InterfaceToCall::create($aggregateClassDefinition->getClassType()->toString(), $method);

            if ($methodToCheck->hasMethodAnnotation($aggregateFactoryAnnotation)) {
                $eventSourcedFactoryMethod = $method;
                break;
            }
        }

        $this->aggregateMessageVersionPropertyName = $aggregateMessageVersionPropertyName;
        $this->eventSourcedFactoryMethod = $eventSourcedFactoryMethod;
    }

    public static function getAggregateRepository(string $aggregateClassName, array $aggregateRepositoryNames, ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService): EventSourcedRepository|StandardRepository
    {
        $aggregateRepository = null;
        foreach ($aggregateRepositoryNames as $aggregateRepositoryName) {
            /** @var StandardRepository|EventSourcedRepository $aggregateRepository */
            $aggregateRepositoryToCheck = $referenceSearchService->get($aggregateRepositoryName);
            if ($aggregateRepositoryToCheck->canHandle($aggregateClassName)) {
                if ($aggregateRepositoryToCheck instanceof RepositoryBuilder) {
                    $aggregateRepositoryToCheck = $aggregateRepositoryToCheck->build($channelResolver, $referenceSearchService);
                }

                $aggregateRepository = $aggregateRepositoryToCheck;
                break;
            }
        }
        Assert::notNull($aggregateRepository, "Aggregate Repository not found for {$aggregateClassName}");

        return $aggregateRepository;
    }
}