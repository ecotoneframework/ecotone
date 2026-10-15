<?php

declare(strict_types=1);

namespace Ecotone\Api\EventSourcing;

use Ecotone\EventSourcing\Tagging\TagKey;

/**
 * licence Apache-2.0
 */
final class AppendCondition
{
    /**
     * @param array<string, array{name: string, value: string, expectedVersion: int, decidedBy?: string[]}> $expectedTagVersions
     * @param array{aggregateType: string, aggregateId: string, expectedVersion: int}|null $aggregateExpectation
     */
    private function __construct(
        private readonly array $expectedTagVersions,
        private readonly ?array $aggregateExpectation,
    ) {
    }

    public static function empty(): self
    {
        return new self([], null);
    }

    public static function forAggregate(string $aggregateType, string $aggregateId, int $expectedVersion): self
    {
        return new self([], [
            'aggregateType' => $aggregateType,
            'aggregateId' => $aggregateId,
            'expectedVersion' => $expectedVersion,
        ]);
    }

    /**
     * @param array<array{name: string, value: string, expectedVersion: int}> $expectedTagVersions
     */
    public static function fromCapturedVersions(array $expectedTagVersions): self
    {
        $indexed = [];
        foreach ($expectedTagVersions as $expectedTagVersion) {
            $indexed[TagKey::of($expectedTagVersion['name'], $expectedTagVersion['value'])] = $expectedTagVersion;
        }

        return new self($indexed, null);
    }

    /**
     * @param array<string, string[]> $decidingScopeNamesByTagKey
     */
    public function withDecidingScopes(array $decidingScopeNamesByTagKey): self
    {
        $expectedTagVersions = $this->expectedTagVersions;
        foreach ($decidingScopeNamesByTagKey as $tagKey => $decidingScopeNames) {
            if (isset($expectedTagVersions[$tagKey])) {
                $expectedTagVersions[$tagKey]['decidedBy'] = $decidingScopeNames;
            }
        }

        return new self($expectedTagVersions, $this->aggregateExpectation);
    }

    public function mergeWith(self $other): self
    {
        return new self(
            [...$this->expectedTagVersions, ...$other->expectedTagVersions],
            $this->aggregateExpectation ?? $other->aggregateExpectation,
        );
    }

    /**
     * @return array<array{name: string, value: string, expectedVersion: int, decidedBy?: string[]}>
     */
    public function expectedTagVersions(): array
    {
        return array_values($this->expectedTagVersions);
    }

    public function hasTagCondition(): bool
    {
        return $this->expectedTagVersions !== [];
    }

    public function hasAggregateCondition(): bool
    {
        return $this->aggregateExpectation !== null;
    }

    public function aggregateType(): ?string
    {
        return $this->aggregateExpectation['aggregateType'] ?? null;
    }

    public function aggregateId(): ?string
    {
        return $this->aggregateExpectation['aggregateId'] ?? null;
    }

    public function expectedAggregateVersion(): ?int
    {
        return $this->aggregateExpectation['expectedVersion'] ?? null;
    }

    public function isEmpty(): bool
    {
        return $this->expectedTagVersions === [] && $this->aggregateExpectation === null;
    }
}
