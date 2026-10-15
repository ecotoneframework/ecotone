<?php

declare(strict_types=1);

namespace Ecotone\Api\EventSourcing;

use Ecotone\Api\Attribute\AggregateType;
use Ecotone\EventSourcing\Tagging\AggregateCounterTag;
use Ecotone\Modelling\AggregateIdString;

use function in_array;

use InvalidArgumentException;

use function is_array;

use ReflectionClass;

use function sprintf;

use Stringable;

/**
 * licence Enterprise
 *
 * Use requires an Ecotone Enterprise licence (see LICENSE-ENTERPRISE; trial: https://ecotone.tech/pricing).
 */
final class EventCriteria
{
    /**
     * @param array<array{name: string, value: string}> $tags
     * @param class-string[] $eventTypes
     * @param self[] $branches
     */
    private function __construct(
        public readonly array $tags,
        public readonly array $eventTypes = [],
        private readonly array $branches = [],
        public readonly int $tagSequenceLowerBound = 0,
    ) {
    }

    public static function tag(string $name, string $value): self
    {
        return new self([['name' => $name, 'value' => $value]]);
    }

    /**
     * @param class-string $aggregateClass
     * @param string|int|Stringable|array<string, string|int|Stringable> $identifier
     */
    public static function aggregate(string $aggregateClass, string|int|Stringable|array $identifier): self
    {
        $aggregateType = (new ReflectionClass($aggregateClass))->getAttributes(AggregateType::class)[0] ?? null;
        if ($aggregateType === null) {
            throw new InvalidArgumentException(sprintf(
                'EventCriteria::aggregate() needs %s to declare #[AggregateType] -- its counter tag is named after the aggregate type.',
                $aggregateClass,
            ));
        }

        return self::tag(
            AggregateCounterTag::nameFor($aggregateType->newInstance()->getName()),
            AggregateIdString::from(is_array($identifier) ? $identifier : [$identifier]),
        );
    }

    public static function any(): self
    {
        return new self([]);
    }

    public function andTag(string $name, string $value): self
    {
        $this->assertNarrowsASingleCriterion('andTag');

        return new self([...$this->tags, ['name' => $name, 'value' => $value]], $this->eventTypes, [], $this->tagSequenceLowerBound);
    }

    public function ofTypes(string ...$eventTypes): self
    {
        $this->assertNarrowsASingleCriterion('ofTypes');

        return new self($this->tags, $eventTypes, [], $this->tagSequenceLowerBound);
    }

    public function afterTagSequence(int $tagSequence): self
    {
        $this->assertNarrowsASingleCriterion('afterTagSequence');

        return new self($this->tags, $this->eventTypes, [], $tagSequence);
    }

    public function or(self $other): self
    {
        return new self([], [], [...$this->branches(), ...$other->branches()]);
    }

    /**
     * @return self[]
     */
    public function branches(): array
    {
        return $this->branches !== [] ? $this->branches : [$this];
    }

    public function matchesEventType(string $eventType): bool
    {
        return $this->eventTypes === [] || in_array($eventType, $this->eventTypes, true);
    }

    private function assertNarrowsASingleCriterion(string $method): void
    {
        if ($this->branches !== []) {
            throw new InvalidArgumentException(sprintf(
                'EventCriteria::%s() cannot narrow an or() combination -- the combined branches would be dropped. Call %s() on each criterion before combining them with or().',
                $method,
                $method,
            ));
        }
    }
}
