<?php

namespace Ecotone\Modelling\EventSourcingExecutor;

use function array_map;

use Ecotone\Api\EventSourcing\Event;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\ApplyMethod;

/**
 * licence Apache-2.0
 */
final class ApplyMethodExecutor
{
    /**
     * @param ApplyMethod[] $applyMethods
     */
    public function __construct(
        private string $aggregateClassName,
        private array $applyMethods,
        private AggregateMethodInvoker $aggregateMethodInvoker,
        private MessageBuilder $messageBuilder,
    ) {
    }

    /**
     * @return string[]
     */
    public function handledEventTypeNames(): array
    {
        return array_map(
            static fn (ApplyMethod $method): string => $method->handledEventType()->toString(),
            $this->applyMethods,
        );
    }

    /**
     * @param Event[] $events
     */
    public function fill(array $events, ?object $existingAggregate): object
    {
        $aggregate = $existingAggregate ?? (new $this->aggregateClassName());
        foreach ($events as $event) {
            $eventPayload = null;
            $metadata = [];
            if ($event instanceof Event) {
                $eventPayload = $event->getPayload();
                Assert::isObject($eventPayload, 'Event returned by repository should be deserialized objects. Got: ' . Type::createFromVariable($eventPayload)->toString());
                $metadata  = $event->metadata;
            } else {
                Assert::isObject($event, 'Event returned by repository should be deserialized objects. Got: ' . Type::createFromVariable($event)->toString());
                $eventPayload = $event;
            }

            $message = $this->messageBuilder->withPayload($eventPayload)
                ->setMultipleHeaders($metadata)
                ->build();
            foreach ($this->applyMethods as $applyMethod) {
                if ($applyMethod->canHandle($eventPayload)) {
                    $this->aggregateMethodInvoker->executeMethod($aggregate, $applyMethod, $message);
                }
            }
        }

        return $aggregate;
    }
}
