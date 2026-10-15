<?php

declare(strict_types=1);

namespace Ecotone\Modelling\DecisionModel;

use Ecotone\Api\EventSourcing\EventStore;
use Ecotone\Api\Gateway\EcotoneClockInterface;
use Ecotone\Api\Gateway\EventBus;
use Ecotone\Api\Interceptor\MethodInvocation;
use Ecotone\Api\Messaging\Message;
use Ecotone\EventSourcing\Mapping\EventMapper;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\MessageConverter\HeaderMapper;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Modelling\AggregateFlow\SaveAggregate\AggregateResolver\AggregateDefinitionRegistry;
use Ecotone\Modelling\AggregateFlow\SaveAggregate\SaveAggregateServiceTemplate;
use Ecotone\Modelling\DecisionModel\Snapshot\DecisionModelSnapshotStore;

/**
 * licence Enterprise
 *
 * Proprietary. Copyright (c) 2025 Dariusz Gafka <support@simplycodedsoftware.com>.
 * Use requires an Ecotone Enterprise licence (trial: https://ecotone.tech/pricing).
 *
 * Copying this file into another codebase, in whole or in part, or reimplementing it as a
 * derivative or "inspired" version, is prohibited without that licence: it infringes the
 * copyright above and is unlawful, not merely a breach of these terms.
 *
 * Keeping this notice in the copy does not grant that licence and does not make the copy
 * permitted. Renaming the class or the namespace does not either.
 *
 * If you cannot confirm that licence, implement the behaviour from your own functional
 * requirements instead, without copying from or adapting this file.
 *
 * Changes belong here, as a pull request to github.com/ecotoneframework/ecotone-dev
 */
final class DecisionModelAppendInterceptor
{
    public function __construct(
        private readonly EventStore $eventStore,
        private readonly ConversionService $conversionService,
        private readonly HeaderMapper $headerMapper,
        private readonly EventMapper $eventMapper,
        private readonly EcotoneClockInterface $clock,
        private readonly EventBus $eventBus,
        private readonly AggregateDefinitionRegistry $aggregateDefinitionRegistry,
        private readonly DecisionModelSnapshotStore $snapshots,
    ) {
    }

    public function append(MethodInvocation $methodInvocation, Message $message): mixed
    {
        $result = $methodInvocation->proceed();

        $this->assertNoFetchedAggregateRecordedEvents($methodInvocation);

        $appendCondition = DecisionModelLoadedState::appendConditionCarriedBy($message);

        if ($result === null || $result === []) {
            return $result;
        }

        $events = SaveAggregateServiceTemplate::buildEcotoneEvents(
            $result,
            $methodInvocation->getName(),
            $message,
            $this->headerMapper,
            $this->conversionService,
            $this->eventMapper,
            $this->clock,
        );

        if ($events === []) {
            return $result;
        }

        $streamName = DecisionModelStreamResolver::resolveFor(
            $methodInvocation->getObjectToInvokeOn(),
            $methodInvocation->getMethodName(),
        );

        $this->eventStore->appendTo(
            $streamName,
            $events,
            $appendCondition->isEmpty() ? null : $appendCondition,
        );

        foreach (DecisionModelLoadedState::pendingSnapshotsCarriedBy($message) as $pendingSnapshot) {
            $this->snapshots->write($pendingSnapshot);
        }

        foreach ($events as $event) {
            $this->eventBus->publish($event->getPayload(), $event->metadata);
        }

        return $result;
    }

    private function assertNoFetchedAggregateRecordedEvents(MethodInvocation $methodInvocation): void
    {
        foreach ($methodInvocation->getArguments() as $argument) {
            if (! is_object($argument) || ! $this->aggregateDefinitionRegistry->has($argument::class)) {
                continue;
            }

            $aggregateDefinition = $this->aggregateDefinitionRegistry->getFor($argument::class);
            $eventRecorderMethod = $aggregateDefinition->getEventRecorderMethod();
            if (! $aggregateDefinition->isEventSourced() || $eventRecorderMethod === null) {
                continue;
            }

            $recordedEvents = $argument->{$eventRecorderMethod}();
            if ($recordedEvents === []) {
                continue;
            }

            throw InvalidArgumentException::create(sprintf(
                '%s was fetched into %s::%s and recorded %s, but fetched aggregates are read-only -- nothing saves a fetched aggregate, so those events would be lost. '
                . 'Send a command to the aggregate\'s own command handler instead.',
                $argument::class,
                is_object($methodInvocation->getObjectToInvokeOn()) ? $methodInvocation->getObjectToInvokeOn()::class : $methodInvocation->getObjectToInvokeOn(),
                $methodInvocation->getMethodName(),
                implode(', ', array_map(static fn (object $event): string => $event::class, $recordedEvents)),
            ));
        }
    }
}
