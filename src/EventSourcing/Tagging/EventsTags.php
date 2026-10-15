<?php

declare(strict_types=1);

namespace Ecotone\EventSourcing\Tagging;

use function array_filter;
use function count;

use Ecotone\Messaging\Support\InvalidArgumentException;

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
final class EventsTags
{
    /**
     * @param array<int, array<array{name: string, value: string, counted: bool}>> $perEvent
     */
    public function __construct(
        private readonly array $perEvent,
    ) {
    }

    public function anyTagged(): bool
    {
        return $this->taggedEventCount() > 0;
    }

    public function taggedEventCount(): int
    {
        return count(array_filter($this->perEvent, static fn (array $tags): bool => $tags !== []));
    }

    public function firstTaggedEventIndex(): int
    {
        foreach ($this->perEvent as $index => $tags) {
            if ($tags !== []) {
                return $index;
            }
        }

        throw InvalidArgumentException::create('No event carries a tag');
    }

    /**
     * @return array<string, array{name: string, value: string}>
     */
    public function counted(): array
    {
        $counted = [];
        foreach ($this->perEvent as $tags) {
            foreach ($tags as $tag) {
                if ($tag['counted']) {
                    $counted[TagKey::of($tag['name'], $tag['value'])] = ['name' => $tag['name'], 'value' => $tag['value']];
                }
            }
        }

        return $counted;
    }

    /**
     * @param array<string, int> $versionsAfterBumpByTagKey
     * @return array<int, array<array{name: string, value: string, sequence: int}>>
     */
    public function sequencedBy(array $versionsAfterBumpByTagKey): array
    {
        $sequenced = [];
        foreach ($this->perEvent as $index => $tags) {
            $sequenced[$index] = [];
            foreach ($tags as $tag) {
                $key = TagKey::of($tag['name'], $tag['value']);
                if ($tag['counted'] && ! isset($versionsAfterBumpByTagKey[$key])) {
                    continue;
                }

                $sequenced[$index][] = ['name' => $tag['name'], 'value' => $tag['value'], 'sequence' => $tag['counted'] ? $versionsAfterBumpByTagKey[$key] : 0];
            }
        }

        return $sequenced;
    }
}
