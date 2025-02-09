<?php

declare(strict_types=1);

namespace Ecotone\Modelling\AggregateFlow\SaveAggregate\AggregateResolver;

use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;

/**
 * licence Apache-2.0
 */
final class AggregateClassDefinition implements DefinedObject
{
    /**
     * @param array<string, string> $aggregateIdentifierMapping
     * @param array<string> $aggregateIdentifierGetMethods
     * @param array<object> $classAnnotations
     */
    public function __construct(
        private string  $className,
        private bool    $isEventSourced,
        private ?string $eventRecorderMethod,
        private ?string $aggregateVersionProperty,
        private bool    $isAggregateVersionAutomaticallyIncreased,
        private array   $aggregateIdentifierMapping,
        private array   $aggregateIdentifierGetMethods,
        private string  $aggregateClassType,
    ) {

    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function isEventSourced(): bool
    {
        return $this->isEventSourced;
    }

    public function isStateStored(): bool
    {
        return ! $this->isEventSourced;
    }

    public function isPureEventSourcedAggregate(): bool
    {
        return $this->isEventSourced && $this->eventRecorderMethod === null;
    }

    public function hasEventRecordingMethod(): bool
    {
        return $this->getEventRecorderMethod() !== null;
    }

    public function getEventRecorderMethod(): ?string
    {
        return $this->eventRecorderMethod;
    }

    public function getAggregateVersionProperty(): ?string
    {
        return $this->aggregateVersionProperty;
    }

    public function isAggregateVersionAutomaticallyIncreased(): bool
    {
        return $this->isAggregateVersionAutomaticallyIncreased;
    }

    /**
     * @return array<string, string>
     */
    public function getAggregateIdentifierMapping(): array
    {
        return $this->aggregateIdentifierMapping;
    }

    /**
     * @return array<string>
     */
    public function getAggregateIdentifierGetMethods(): array
    {
        return $this->aggregateIdentifierGetMethods;
    }

    public function getAggregateClassType(): string
    {
        return $this->aggregateClassType;
    }

    public function getDefinition(): Definition
    {
        return new Definition(self::class, [
            $this->className,
            $this->isEventSourced,
            $this->eventRecorderMethod,
            $this->aggregateVersionProperty,
            $this->isAggregateVersionAutomaticallyIncreased,
            $this->aggregateIdentifierMapping,
            $this->aggregateIdentifierGetMethods,
            $this->aggregateClassType,
        ]);
    }
}
