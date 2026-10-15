<?php

declare(strict_types=1);

namespace Ecotone\EventSourcing\EventStore;

use Ecotone\Api\EventSourcing\Event;
use Ecotone\Api\ExtensionObject\MediaType;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Handler\Type;

use function is_array;

/**
 * licence Apache-2.0
 */
final class AsGivenRecordedEventFormat implements RecordedEventFormat
{
    public function __construct(
        private ConversionService $conversionService,
    ) {
    }

    public function recordedFrom(object|array $event): Event
    {
        if ($event instanceof Event) {
            return $event;
        }

        if (is_array($event)) {
            return Event::createWithType('array', $event);
        }

        return Event::create($event);
    }

    public function recordedDataOf(Event $recordedEvent): Event
    {
        $payload = $recordedEvent->getPayload();
        if (is_array($payload)) {
            return $recordedEvent;
        }

        return Event::createWithType(
            $recordedEvent->eventName,
            $this->conversionService->convert($payload, Type::createFromVariable($payload), MediaType::createApplicationXPHP(), Type::array(), MediaType::createApplicationXPHP()),
            $recordedEvent->metadata,
        );
    }
}
