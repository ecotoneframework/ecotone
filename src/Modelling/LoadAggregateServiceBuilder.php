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
use Ecotone\Modelling\Annotation\AggregateEvents;
use Ecotone\Modelling\Annotation\AggregateFactory;
use Ecotone\Modelling\Annotation\TargetAggregateVersion;

class LoadAggregateServiceBuilder extends InputOutputMessageHandlerBuilder
{
    private string $aggregateClassName;
    private string $methodName;
    private ?array $versionMapping;
    private bool $dropMessageOnNotFound;
    private array $aggregateRepositoryReferenceNames;
    private ?string $handledMessageClassName;
    private ?string $eventSourcedFactoryMethod;

    private function __construct(ClassDefinition $aggregateClassName, string $methodName, ?ClassDefinition $handledMessageClass, bool $dropMessageOnNotFound)
    {
        $this->aggregateClassName      = $aggregateClassName;
        $this->methodName              = $methodName;
        $this->handledMessageClassName = $handledMessageClass;
        $this->dropMessageOnNotFound   = $dropMessageOnNotFound;

        $this->initialize($aggregateClassName, $handledMessageClass);
    }

    public static function create(ClassDefinition $aggregateClassDefinition, string $methodName, ?ClassDefinition $handledMessageClass, bool $dropMessageOnNotFound): self
    {
        return new self($aggregateClassDefinition, $methodName, $handledMessageClass, $dropMessageOnNotFound);
    }

    public function getInterceptedInterface(InterfaceToCallRegistry $interfaceToCallRegistry): InterfaceToCall
    {
        return $interfaceToCallRegistry->getFor($this->aggregateClassName, $this->methodName);
    }

    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService): MessageHandler
    {
        $aggregateRepository = $this->getAggregateRepository($referenceSearchService);
        if ($aggregateRepository instanceof EventSourcedRepository && !$this->eventSourcedFactoryMethod) {
            $repositoryClass = get_class($aggregateRepository);
            throw InvalidArgumentException::create("Based on your repository {$repositoryClass}, you want to create Event Sourced Aggregate. You must define static method marked with @AggregateFactory for aggregate recreation from events");
        }

        return ServiceActivatorBuilder::createWithDirectReference(
            new LoadAggregateService(
                $aggregateRepository,
                $this->aggregateClassName,
                $this->methodName,
                $this->versionMapping,
                new PropertyReaderAccessor(),
                $this->eventSourcedFactoryMethod,
                $this->dropMessageOnNotFound
            ),
            "load"
        )->build($channelResolver, $referenceSearchService);
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
        $aggregateVersionPropertyName       = null;

        $aggregateFactoryAnnotation = TypeDescriptor::create(AggregateFactory::class);
        foreach ($aggregateClassDefinition->getPublicMethodNames() as $method) {
            $methodToCheck = InterfaceToCall::create($aggregateClassDefinition->getClassType()->toString(), $method);

            if ($methodToCheck->hasMethodAnnotation($aggregateFactoryAnnotation)) {
                $factoryMethodInterface = InterfaceToCall::create($aggregateClassDefinition->getClassType()->toString(), $method);
                Assert::isTrue($factoryMethodInterface->hasSingleArgument(), "Event sourced factory method {$aggregateClassDefinition}:{$method} should contain only one iterable parameter");
                Assert::isTrue($factoryMethodInterface->isStaticallyCalled(), "Event sourced factory method {$aggregateClassDefinition}:{$method} should be static");
                Assert::isTrue($factoryMethodInterface->getFirstParameter()->getTypeDescriptor()->isIterable(), "Event sourced factory method {$aggregateClassDefinition}:{$method} should type hint for array or iterable");

                break;
            }
        }

        $aggregateVersionMapping = null;
        if ($handledMessageClassName) {
            $targetAggregateVersion            = TypeDescriptor::create(TargetAggregateVersion::class);
            foreach ($handledMessageClassName->getProperties() as $property) {
                if ($property->hasAnnotation($targetAggregateVersion)) {
                    $aggregateVersionMapping[$property->getName()] = $aggregateVersionPropertyName;
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

        if (!$aggregateVersionMapping && $aggregateVersionPropertyName) {
            $aggregateVersionMapping[$aggregateVersionPropertyName] = $aggregateVersionPropertyName;
        }

        $this->versionMapping = $aggregateVersionMapping;
        $this->eventSourcedFactoryMethod = $eventSourcedFactoryMethod;
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
}