<?php

declare(strict_types=1);

namespace Ecotone\EventSourcing\Mapping;

use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Modelling\Event;

/**
 * licence Apache-2.0
 */
final class EventMapper
{
    private array $eventToNameMapping;
    private array $nameToEventMapping;

    private function __construct(array $eventToNameMapping, array $nameToEventMapping)
    {
        $this->eventToNameMapping = $eventToNameMapping;
        $this->nameToEventMapping = $nameToEventMapping;
    }

    public static function createEmpty(): self
    {
        return new self([], []);
    }

    public static function createWith(array $eventToNameMapping, array $nameToEventMapping): static
    {
        return new self($eventToNameMapping, $nameToEventMapping);
    }

    public function mapNameToEventType(string $name): string
    {
        if ($name === Type::ARRAY) {
            return Type::ARRAY;
        }

        if (array_key_exists($name, $this->nameToEventMapping)) {
            return $this->nameToEventMapping[$name];
        }

        return $name;
    }

    public function mapEventToName(object $event): string
    {
        $type = $event instanceof Event ? $event->getEventName() : $event::class;
        if (array_key_exists($type, $this->eventToNameMapping)) {
            return $this->eventToNameMapping[$type];
        }

        return $type;
    }

    public function compile(): Definition
    {
        return new Definition(self::class, [
            $this->eventToNameMapping,
            $this->nameToEventMapping,
        ], 'createWith');
    }
}
