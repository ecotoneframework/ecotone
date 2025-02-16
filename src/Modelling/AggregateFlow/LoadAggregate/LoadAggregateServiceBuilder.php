<?php

namespace Ecotone\Modelling\AggregateFlow\LoadAggregate;

use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\InterfaceToCallReference;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Handler\ClassDefinition;
use Ecotone\Messaging\Handler\Enricher\PropertyEditorAccessor;
use Ecotone\Messaging\Handler\Enricher\PropertyReaderAccessor;
use Ecotone\Messaging\Handler\ExpressionEvaluationService;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Logger\LoggingGateway;
use Ecotone\Messaging\Handler\Processor\InterceptedMessageProcessorBuilder;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Modelling\Attribute\AggregateVersion;
use Ecotone\Modelling\Attribute\EventSourcingAggregate;
use Ecotone\Modelling\Attribute\TargetAggregateVersion;
use Ecotone\Modelling\BaseEventSourcingConfiguration;
use Ecotone\Modelling\EventSourcingExecutor\EventSourcingHandlerExecutorBuilder;
use Ecotone\Modelling\LazyEventSourcedRepository;
use Ecotone\Modelling\LazyStandardRepository;
use Psr\Container\ContainerInterface;

/**
 * licence Apache-2.0
 */
class LoadAggregateServiceBuilder implements InterceptedMessageProcessorBuilder
{
    private string $aggregateClassName;
    private string $methodName;
    private ?string $messageVersionPropertyName;
    private ?string $aggregateVersionPropertyName = null;
    private array $aggregateRepositoryReferenceNames;
    private Definition $eventSourcingHandlerExecutor;
    private LoadAggregateMode $loadAggregateMode;
    private bool $isEventSourced;
    private bool $isAggregateVersionAutomaticallyIncreased = true;

    private function __construct(ClassDefinition $aggregateClassName, string $methodName, ?ClassDefinition $handledMessageClass, LoadAggregateMode $loadAggregateMode, InterfaceToCallRegistry $interfaceToCallRegistry, private BaseEventSourcingConfiguration $eventSourcingConfiguration)
    {
        $this->aggregateClassName      = $aggregateClassName;
        $this->methodName              = $methodName;
        $this->loadAggregateMode = $loadAggregateMode;

        $this->initialize($aggregateClassName, $handledMessageClass, $interfaceToCallRegistry);
    }

    public static function create(ClassDefinition $aggregateClassDefinition, string $methodName, ?ClassDefinition $handledMessageClass, LoadAggregateMode $loadAggregateMode, InterfaceToCallRegistry $interfaceToCallRegistry, BaseEventSourcingConfiguration $eventSourcingConfiguration): self
    {
        return new self($aggregateClassDefinition, $methodName, $handledMessageClass, $loadAggregateMode, $interfaceToCallRegistry, $eventSourcingConfiguration);
    }

    public function compile(MessagingContainerBuilder $builder, array $aroundInterceptors = []): Definition
    {
        if (! $builder->has(PropertyEditorAccessor::class)) {
            $builder->register(PropertyEditorAccessor::class, new Definition(PropertyEditorAccessor::class, [
                new Reference(ExpressionEvaluationService::REFERENCE),
            ], 'create'));
        }

        if ($this->isEventSourced) {
            return $this->loadEventSourcingAggregateService();
        } else {
            return $this->loadStateBasedAggregateService();
        }
    }

    /**
     * @param string[] $aggregateRepositoryReferenceNames
     */
    public function withAggregateRepositoryFactories(array $aggregateRepositoryReferenceNames): self
    {
        $this->aggregateRepositoryReferenceNames = $aggregateRepositoryReferenceNames;

        return $this;
    }

    private function initialize(ClassDefinition $aggregateClassDefinition, ?ClassDefinition $handledMessageClassName, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        $this->isEventSourced = $aggregateClassDefinition->hasClassAnnotation(TypeDescriptor::create(EventSourcingAggregate::class));
        $aggregateMessageVersionPropertyName = null;
        if ($handledMessageClassName) {
            $targetAggregateVersion            = TypeDescriptor::create(TargetAggregateVersion::class);
            foreach ($handledMessageClassName->getProperties() as $property) {
                if ($property->hasAnnotation($targetAggregateVersion)) {
                    $aggregateMessageVersionPropertyName = $property->getName();
                }
            }
        }
        $versionAnnotation = TypeDescriptor::create(AggregateVersion::class);
        foreach ($aggregateClassDefinition->getProperties() as $property) {
            if ($property->hasAnnotation($versionAnnotation)) {
                /** @var AggregateVersion $annotation */
                $annotation = $property->getAnnotation($versionAnnotation);
                $this->aggregateVersionPropertyName = $property->getName();
                $this->isAggregateVersionAutomaticallyIncreased = $annotation->isAutoIncreased();
            }
        }

        $this->messageVersionPropertyName = $aggregateMessageVersionPropertyName;
        $this->eventSourcingHandlerExecutor = EventSourcingHandlerExecutorBuilder::createFor($aggregateClassDefinition, $this->isEventSourced, $interfaceToCallRegistry);
    }

    private function loadEventSourcingAggregateService(): Definition
    {
        $repository = new Definition(LazyEventSourcedRepository::class, [
            array_map(fn ($id) => new Reference($id), $this->aggregateRepositoryReferenceNames),
        ], 'create')
        ;

        return new Definition(LoadEventSourcingAggregateService::class, [
            $repository,
            $this->aggregateClassName,
            $this->methodName,
            $this->messageVersionPropertyName,
            $this->aggregateVersionPropertyName,
            $this->isAggregateVersionAutomaticallyIncreased,
            new Reference(PropertyReaderAccessor::class),
            new Reference(PropertyEditorAccessor::class),
            $this->eventSourcingHandlerExecutor,
            new Definition(LoadAggregateMode::class, [$this->loadAggregateMode->getType()]),
            new Reference(ContainerInterface::class),
            $this->eventSourcingConfiguration,
            Reference::to(LoggingGateway::class),
        ]);
    }

    private function loadStateBasedAggregateService(): Definition
    {
        $repository = new Definition(LazyStandardRepository::class, [
            array_map(fn ($id) => new Reference($id), $this->aggregateRepositoryReferenceNames),
        ], 'create');

        return new Definition(LoadStateBasedAggregateService::class, [
            $repository,
            $this->aggregateClassName,
            $this->methodName,
            $this->messageVersionPropertyName,
            $this->aggregateVersionPropertyName,
            $this->isAggregateVersionAutomaticallyIncreased,
            new Reference(PropertyReaderAccessor::class),
            new Reference(PropertyEditorAccessor::class),
            new Definition(LoadAggregateMode::class, [$this->loadAggregateMode->getType()]),
        ]);
    }

    public function getInterceptedInterface(): InterfaceToCallReference
    {
        return InterfaceToCallReference::create($this->aggregateClassName, $this->methodName);
    }
}
