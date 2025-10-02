<?php

declare(strict_types=1);

namespace Ecotone\Modelling\AggregateFlow\SaveAggregate;

use Ecotone\EventSourcing\Mapping\EventMapper;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Handler\Enricher\PropertyReaderAccessor;
use Ecotone\Messaging\Handler\MessageProcessor;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageConverter\HeaderMapper;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\AggregateFlow\SaveAggregate\AggregateResolver\AggregateDefinitionRegistry;
use Ecotone\Modelling\AggregateFlow\SaveAggregate\AggregateResolver\ResolvedAggregate;
use Ecotone\Modelling\AggregateMessage;
use Ecotone\Modelling\Repository\AggregateRepository;

/**
 * licence Apache-2.0
 */
final class SaveAggregateTestSetupService implements MessageProcessor
{
    public function __construct(
        private AggregateDefinitionRegistry $aggregateDefinitionRegistry,
        private PropertyReaderAccessor $propertyReaderAccessor,
        private ConversionService $conversionService,
        private HeaderMapper $headerMapper,
        private EventMapper $eventMapper,
        private AggregateRepository $aggregateRepository,
    ) {
    }

    public function process(Message $message): Message|null
    {
        $resolvedAggregate = $this->resolveAggregate($message);
        $metadata = MessageHeaders::unsetNonUserKeys($message->getHeaders()->headers());

        if (! $resolvedAggregate) {
            return null;
        }

        $version = $resolvedAggregate->getVersionBeforeHandling();

        $this->aggregateRepository->save(
            $resolvedAggregate,
            $metadata,
            $version
        );

        /** Clear internally recorded events */
        if ($resolvedAggregate->getAggregateClassDefinition()->hasEventRecordingMethod()) {
            call_user_func([$resolvedAggregate->getAggregateInstance(), $resolvedAggregate->getAggregateClassDefinition()->getEventRecorderMethod()]);
        }

        return null;
    }

    private function resolveAggregate(Message $message): ResolvedAggregate
    {
        $aggregateDefinition = $this->aggregateDefinitionRegistry->getFor(Type::object($message->getHeaders()->get(AggregateMessage::TEST_SETUP_AGGREGATE_CLASS)));
        $calledAggregateInstance = $message->getHeaders()->containsKey(AggregateMessage::TEST_SETUP_AGGREGATE_INSTANCE) ? $message->getHeaders()->get(AggregateMessage::TEST_SETUP_AGGREGATE_INSTANCE) : null;
        $versionBeforeHandling = $message->getHeaders()->containsKey(AggregateMessage::TEST_SETUP_AGGREGATE_VERSION) ? $message->getHeaders()->get(AggregateMessage::TEST_SETUP_AGGREGATE_VERSION) : 0;

        $identifiers = SaveAggregateServiceTemplate::getAggregateIds(
            $this->propertyReaderAccessor,
            $message->getHeaders()->headers(),
            $calledAggregateInstance,
            $aggregateDefinition,
            $aggregateDefinition->isEventSourced(),
        );

        $events = SaveAggregateServiceTemplate::buildEcotoneEvents(
            $this->resolveEvents($message),
            $aggregateDefinition->getDefinition()->getClassName(),
            MessageBuilder::fromMessage($message)
                ->removeHeaders([
                    AggregateMessage::TEST_SETUP_AGGREGATE_CLASS,
                    AggregateMessage::TEST_SETUP_AGGREGATE_INSTANCE,
                    AggregateMessage::TEST_SETUP_AGGREGATE_VERSION,
                ])
                ->build(),
            $this->headerMapper,
            $this->conversionService,
            $this->eventMapper,
        );

        $enrichedEvents = SaveAggregateServiceTemplate::enrichAggregateEvents(
            events: $events,
            versionBeforeHandling: (int) $versionBeforeHandling,
            identifiers: $identifiers,
            aggregateDefinition: $aggregateDefinition
        );

        return new ResolvedAggregate(
            aggregateClassDefinition: $aggregateDefinition,
            isNewInstance: true,
            aggregateInstance: $calledAggregateInstance,
            versionBeforeHandling: $versionBeforeHandling,
            identifiers: $identifiers,
            events: $enrichedEvents,
        );
    }

    private function resolveEvents(Message $message): array
    {
        if ($message->getHeaders()->containsKey(AggregateMessage::TEST_SETUP_AGGREGATE_EVENTS)) {
            return $message->getHeaders()->get(AggregateMessage::TEST_SETUP_AGGREGATE_EVENTS);
        }

        return [];
    }
}
