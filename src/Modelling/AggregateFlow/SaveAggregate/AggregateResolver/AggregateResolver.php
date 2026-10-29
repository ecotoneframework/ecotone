<?php

declare(strict_types=1);

namespace Ecotone\Modelling\AggregateFlow\SaveAggregate\AggregateResolver;

use Ecotone\EventSourcing\Mapping\EventMapper;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Handler\Enricher\PropertyEditorAccessor;
use Ecotone\Messaging\Handler\Enricher\PropertyPath;
use Ecotone\Messaging\Handler\Enricher\PropertyReaderAccessor;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageConverter\HeaderMapper;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\AggregateFlow\SaveAggregate\SaveAggregateServiceTemplate;
use Ecotone\Modelling\AggregateMessage;
use Ecotone\Modelling\Event;
use Ecotone\Modelling\EventSourcingExecutor\GroupedEventSourcingExecutor;

/**
 * licence Apache-2.0
 */
final class AggregateResolver
{
    public function __construct(
        private AggregateDefinitionRegistry $aggregateDefinitionRegistry,
        private GroupedEventSourcingExecutor $eventSourcingExecutor,
        private PropertyEditorAccessor $propertyEditorAccessor,
        private PropertyReaderAccessor $propertyReaderAccessor,
        private ConversionService $conversionService,
        private HeaderMapper $headerMapper,
        private EventMapper $eventMapper,
    ) {

    }

    /**
     * @return ResolvedAggregate[]
     */
    public function resolve(Message $message): array
    {
        return $this->resolveMultipleAggregates($message, ! $message->getHeaders()->containsKey(AggregateMessage::CALLED_AGGREGATE_INSTANCE));
    }

    /**
     * @param Event[] $events
     */
    public function resolveAggregateInstance(array $events, AggregateClassDefinition $aggregateDefinition, ?object $actualAggregate): object
    {
        if ($aggregateDefinition->isEventSourced()) {
            return $this->eventSourcingExecutor->fillFor($aggregateDefinition->getClassName(), $actualAggregate, $events);
        }

        return $actualAggregate;
    }

    /**
     * @return Event[]
     */
    private function resolveEvents(AggregateClassDefinition $aggregateDefinition, ?object $actualAggregate, Message $message): array
    {
        if ($message->getHeaders()->containsKey(AggregateMessage::RECORDED_AGGREGATE_EVENTS)) {
            return $message->getHeaders()->get(AggregateMessage::RECORDED_AGGREGATE_EVENTS);
        }

        /** Pure Event Sourced Aggregates returns events directly, therefore it lands as message payload */
        if ($aggregateDefinition->isPureEventSourcedAggregate()) {
            if ($message->getHeaders()->containsKey(AggregateMessage::NULL_EXECUTION_RESULT)
                && $message->getHeaders()->get(AggregateMessage::NULL_EXECUTION_RESULT) === true
            ) {
                return [];
            }
            $returnType = Type::createFromVariable($message->getPayload());
            if ($this->isNewAggregateInstanceReturned($returnType)) {
                return [];
            }

            Assert::isTrue($returnType->isIterable(), "Pure event sourced aggregate should return iterable of events, but got {$returnType->toString()}");
            return $message->getPayload();
        }

        /** In other scenario than pure event sourced aggregate, we have to deal with aggregate instance */
        Assert::notNull($actualAggregate, "Aggregate {$aggregateDefinition->getClassName()} was not found. Can't fetch events for it.");

        if ($aggregateDefinition->hasEventRecordingMethod()) {
            return call_user_func([$actualAggregate, $aggregateDefinition->getEventRecorderMethod()]);
        }

        return [];
    }

    public function resolveMultipleAggregates(Message $message, bool $isNewInstance): array
    {
        $resolvedAggregates = [];
        /** This will be null for factory methods */
        $calledAggregateInstance = $message->getHeaders()->containsKey(AggregateMessage::CALLED_AGGREGATE_INSTANCE) ? $message->getHeaders()->get(AggregateMessage::CALLED_AGGREGATE_INSTANCE) : null;

        Assert::isTrue($message->getHeaders()->containsKey(AggregateMessage::CALLED_AGGREGATE_CLASS), 'No aggregate class name was found in headers');
        $aggregateDefinition = $this->aggregateDefinitionRegistry->getFor($message->getHeaders()->get(AggregateMessage::CALLED_AGGREGATE_CLASS));

        if ($calledAggregateInstance || (is_null($calledAggregateInstance) && $aggregateDefinition->isPureEventSourcedAggregate())) {
            $resolvedAggregate = $this->resolveSingleAggregate($aggregateDefinition, $calledAggregateInstance, $message, $isNewInstance);

            if ($resolvedAggregate) {
                $resolvedAggregates[] = $resolvedAggregate;
            }
        }

        $returnType = Type::createFromVariable($message->getPayload());
        if ($this->isNewAggregateInstanceReturned($returnType)) {
            $returnedResolvedAggregates = $this->resolveMultipleAggregates(
                MessageBuilder::fromMessage($message)
                    ->setPayload([])
                    ->setHeader(AggregateMessage::CALLED_AGGREGATE_INSTANCE, $message->getPayload())
                    ->setHeader(AggregateMessage::CALLED_AGGREGATE_CLASS, $returnType->getTypeHint())
                    ->setHeader(AggregateMessage::TARGET_VERSION, 0)
                    ->removeHeaders([AggregateMessage::AGGREGATE_ID, AggregateMessage::NULL_EXECUTION_RESULT])
                    ->build(),
                true,
            );

            if (count($resolvedAggregates) === count($returnedResolvedAggregates)) {
                if ($resolvedAggregates[0]->getAggregateInstance() === $returnedResolvedAggregates[0]->getAggregateInstance()) {
                    return $resolvedAggregates;
                }
            }

            $resolvedAggregates = array_merge($resolvedAggregates, $returnedResolvedAggregates);
        }

        return $resolvedAggregates;
    }

    public function resolveSingleAggregate(AggregateClassDefinition $aggregateDefinition, null|object $calledAggregateInstance, Message $message, bool $isNewInstance): ResolvedAggregate|null
    {
        $events = SaveAggregateServiceTemplate::buildEcotoneEvents(
            $this->resolveEvents($aggregateDefinition, $calledAggregateInstance, $message),
            $aggregateDefinition->getDefinition()->getClassName(),
            $message,
            $this->headerMapper,
            $this->conversionService,
            $this->eventMapper,
        );

        if ($this->hasReturnedNoEvents($aggregateDefinition, $events)) {
            return null;
        }

        $versionBeforeHandling = $this->getVersionBeforeHandling($message, $aggregateDefinition, $calledAggregateInstance);
        $instance = $this->resolveAggregateInstance($events, $aggregateDefinition, $calledAggregateInstance);

        SaveAggregateServiceTemplate::enrichVersionIfNeeded(
            $this->propertyEditorAccessor,
            SaveAggregateServiceTemplate::resolveVersionBeforeHandling($message),
            $instance,
            $message,
            $aggregateDefinition->getAggregateVersionProperty(),
            $aggregateDefinition->isAggregateVersionAutomaticallyIncreased(),
        );

        $identifiers = SaveAggregateServiceTemplate::getAggregateIds(
            $this->propertyReaderAccessor,
            $message->getHeaders()->headers(),
            $instance,
            $aggregateDefinition,
            $aggregateDefinition->isEventSourced(),
        );

        $enrichedEvents = SaveAggregateServiceTemplate::enrichAggregateEvents(
            events: $events,
            versionBeforeHandling: (int) $versionBeforeHandling,
            identifiers: $identifiers,
            aggregateDefinition: $aggregateDefinition
        );

        return new ResolvedAggregate(
            $aggregateDefinition,
            $isNewInstance,
            $instance,
            $versionBeforeHandling,
            $identifiers,
            $enrichedEvents,
        );
    }

    public function isNewAggregateInstanceReturned(Type $returnType): bool
    {
        return $this->aggregateDefinitionRegistry->has($returnType);
    }

    public function hasReturnedNoEvents(AggregateClassDefinition $aggregateDefinition, array $events): bool
    {
        return $aggregateDefinition->isPureEventSourcedAggregate() && $events === [];
    }

    public function getVersionBeforeHandling(Message $message, AggregateClassDefinition $aggregateDefinition, null|object $calledAggregateInstance): ?int
    {
        $versionBeforeHandling = $message->getHeaders()->containsKey(AggregateMessage::TARGET_VERSION) ? $message->getHeaders()->get(AggregateMessage::TARGET_VERSION) : null;

        if ($versionBeforeHandling !== null) {
            return $versionBeforeHandling;
        }

        if ($calledAggregateInstance === null) {
            return 0;
        }

        if ($aggregateDefinition->getAggregateVersionProperty()) {
            if ($aggregateDefinition->isAggregateVersionAutomaticallyIncreased() && $aggregateDefinition->isStateStored()) {
                /** Version could already been bumped up, we are unaware which version was used before handling */

                return null;
            }

            return $this->propertyReaderAccessor->getPropertyValue(PropertyPath::createWith($aggregateDefinition->getAggregateVersionProperty()), $calledAggregateInstance);
        }

        return null;
    }
}
