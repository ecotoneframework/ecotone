<?php

declare(strict_types=1);

namespace Ecotone\Modelling\AggregateFlow\SaveAggregate;

use Ecotone\Api\EventSourcing\Event;
use Ecotone\Api\Gateway\EventBus;
use Ecotone\Api\Messaging\Message;
use Ecotone\Messaging\Handler\Enricher\PropertyReaderAccessor;
use Ecotone\Messaging\Handler\MessageProcessor;
use Ecotone\Messaging\Support\FrameworkHeaders;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\AggregateFlow\SaveAggregate\AggregateResolver\AggregateResolver;
use Ecotone\Modelling\DecisionModel\DecisionModelLoadedState;
use Ecotone\Modelling\Repository\AggregateRepository;

/**
 * licence Apache-2.0
 */
final class SaveAggregateService implements MessageProcessor
{
    public const NO_SNAPSHOT_THRESHOLD = 0;

    public function __construct(
        private AggregateRepository $aggregateRepository,
        private PropertyReaderAccessor $propertyReaderAccessor,
        private AggregateResolver $aggregateResolver,
        private EventBus $eventBus,
    ) {

    }

    public function process(Message $message): Message|null
    {
        $resolvedAggregates = $this->aggregateResolver->resolve($message);
        $metadata = DecisionModelLoadedState::carryInto(
            $message,
            FrameworkHeaders::unsetNonUserKeys($message->getHeaders()->headers()),
        );

        if (! $resolvedAggregates) {
            return MessageBuilder::fromMessage($message)->build();
        }

        foreach ($resolvedAggregates as $key => $resolvedAggregate) {
            $versionAfterHandling = $this->aggregateRepository->save($resolvedAggregate, $metadata);

            /** For ORM identifier may be assigned after saving */
            $resolvedAggregates[$key] = $resolvedAggregate->withIdentifiers(
                SaveAggregateServiceTemplate::getAggregateIds(
                    $this->propertyReaderAccessor,
                    $message->getHeaders()->headers(),
                    $resolvedAggregate->getAggregateInstance(),
                    $resolvedAggregate->getAggregateClassDefinition(),
                    true,
                )
            )->withVersionAfterHandling($versionAfterHandling);
        }

        foreach ($resolvedAggregates as $resolvedAggregate) {
            $this->publishEvents($resolvedAggregate->getEvents());
        }

        return SaveAggregateServiceTemplate::buildReplyMessage(
            $resolvedAggregates[0]->isNewInstance(),
            $resolvedAggregates[0]->isNewInstance() ? $resolvedAggregates[0]->getIdentifiers() : [],
            $resolvedAggregates[0]->getVersionAfterHandling(),
            $message,
        );
    }

    /**
     * @param Event[] $events
     */
    private function publishEvents(array $events): void
    {
        foreach ($events as $event) {
            $this->eventBus->publish($event->getPayload(), $event->metadata);
        }
    }
}
