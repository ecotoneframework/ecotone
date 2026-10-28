<?php

declare(strict_types=1);

namespace Ecotone\EventSourcing\Tagging;

use function array_diff_key;
use function array_filter;

use const ARRAY_FILTER_USE_KEY;

use function ksort;

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
final class AppendedTags
{
    /** @var array<string, array{name: string, value: string}> */
    private readonly array $involved;

    /** @var array<string, TagVersionChange> */
    private readonly array $changes;

    /**
     * @param array<string, array{name: string, value: string, expectedVersion: int, decidedBy?: string[], bumpedOnAppend?: bool}> $conditionVersions
     * @param array<string, array{name: string, value: string}> $aggregateCounterTags
     * @param array<string, string> $aggregateTypesByCounterTagKey
     */
    public function __construct(
        private readonly EventsTags $eventsTags,
        private readonly array $conditionVersions,
        array $aggregateCounterTags,
        private readonly array $aggregateTypesByCounterTagKey,
        bool $bumpsEveryConditionTag,
        bool $incrementsUncapturedTags,
    ) {
        $involved = [];
        $changes = [];
        foreach ($eventsTags->counted() as $key => $carriedTag) {
            $involved[$key] = $carriedTag;
            $changes[$key] = $incrementsUncapturedTags ? TagVersionChange::Increment : TagVersionChange::GuardedBump;
        }
        foreach ($conditionVersions as $key => $conditionVersion) {
            $involved[$key] = ['name' => $conditionVersion['name'], 'value' => $conditionVersion['value']];
            $changes[$key] = isset($changes[$key]) || $bumpsEveryConditionTag || ($conditionVersion['bumpedOnAppend'] ?? false)
                ? TagVersionChange::GuardedBump
                : TagVersionChange::Check;
        }
        foreach ($aggregateCounterTags as $key => $aggregateCounterTag) {
            $involved[$key] = $aggregateCounterTag;
            $changes[$key] = TagVersionChange::GuardedBump;
        }

        ksort($involved);
        $this->involved = $involved;
        $this->changes = $changes;
    }

    public function involvesAnyTag(): bool
    {
        return $this->involved !== [] || $this->eventsTags->anyTagged();
    }

    /**
     * @return array<string, array{name: string, value: string}>
     */
    public function needingCapture(): array
    {
        return array_diff_key($this->involved, $this->conditionVersions, $this->incremented());
    }

    /**
     * @return array<string, array{name: string, value: string}>
     */
    public function incremented(): array
    {
        return array_filter(
            $this->involved,
            fn (string $key): bool => $this->changes[$key] === TagVersionChange::Increment,
            ARRAY_FILTER_USE_KEY,
        );
    }

    /**
     * @param array<string, array{name: string, value: string, expectedVersion: int}> $captured
     * @return array<string, array{name: string, value: string, change: TagVersionChange, expectedVersion: ?int, aggregateType?: string, decidedBy?: string[]}>
     */
    public function expectedVersions(array $captured): array
    {
        $expected = [];
        foreach ($this->involved as $key => $tag) {
            $change = $this->changes[$key];
            $expected[$key] = [
                'name' => $tag['name'],
                'value' => $tag['value'],
                'change' => $change,
                'expectedVersion' => $change === TagVersionChange::Increment
                    ? null
                    : $this->conditionVersions[$key]['expectedVersion'] ?? $captured[$key]['expectedVersion'],
            ];

            if (isset($this->aggregateTypesByCounterTagKey[$key])) {
                $expected[$key]['aggregateType'] = $this->aggregateTypesByCounterTagKey[$key];
            }

            if (isset($this->conditionVersions[$key]['decidedBy'])) {
                $expected[$key]['decidedBy'] = $this->conditionVersions[$key]['decidedBy'];
            }
        }

        return $expected;
    }

    /**
     * @param array<string, array{name: string, value: string, change: TagVersionChange, expectedVersion: ?int}> $expectedVersions
     * @param array<string, int> $incrementedVersions
     * @return array<int, array<array{name: string, value: string, sequence: int}>>
     */
    public function sequencedAfterBump(array $expectedVersions, array $incrementedVersions): array
    {
        $versionsAfterBump = $incrementedVersions;
        foreach ($expectedVersions as $key => $expected) {
            if ($expected['change'] === TagVersionChange::GuardedBump) {
                $versionsAfterBump[$key] = $expected['expectedVersion'] + 1;
            }
        }

        return $this->eventsTags->sequencedBy($versionsAfterBump);
    }
}
