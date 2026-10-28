<?php

declare(strict_types=1);

namespace Ecotone\Projecting\TaggedStream;

use function array_map;
use function array_unique;
use function count;

use Ecotone\Api\Projecting\FromTaggedStream;
use Ecotone\Api\Projecting\Partitioned;
use Ecotone\Messaging\Config\ConfigurationException;

use function implode;
use function in_array;
use function sort;
use function sprintf;

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
final class TaggedStreamProjectionGuard
{
    /**
     * @param FromTaggedStream[] $taggedStreams
     * @param string[] $declaredTagNames
     */
    public static function assertReadableByTagPartitions(string $projectionName, array $taggedStreams, ?Partitioned $partitioned, bool $hasOtherSources, array $declaredTagNames): void
    {
        if ($taggedStreams === []) {
            return;
        }

        if (count($taggedStreams) > 1) {
            throw ConfigurationException::create(sprintf(
                "Projection '%s' declares #[FromTaggedStream] more than once (%s), but its partitions are the values of one tag, and values of two tags would share partition keys. Keep one #[FromTaggedStream], or split the projection into one projection per tag.",
                $projectionName,
                implode(', ', array_map(static fn (FromTaggedStream $taggedStream): string => $taggedStream->tag, $taggedStreams)),
            ));
        }

        $tag = $taggedStreams[0]->tag;
        if ($hasOtherSources) {
            throw ConfigurationException::create("Projection '{$projectionName}' combines #[FromTaggedStream('{$tag}')] with #[FromStream] or #[FromAggregateStream], but its partitions are the values of the tag '{$tag}', and the other source is partitioned differently or not at all. Keep #[FromTaggedStream] alone, or move the other source into a projection of its own.");
        }

        if ($partitioned === null) {
            throw ConfigurationException::create("Projection '{$projectionName}' reads #[FromTaggedStream('{$tag}')] without #[Partitioned], but a tagged stream is read one value of the tag at a time, positioned by that value's tag sequence, so it has no single position to read it globally by. Either add #[Partitioned] to keep one partition per value of '{$tag}', or read the events with #[FromStream] for a projection over the whole stream.");
        }

        if ($partitioned->partitionHeaderName !== null) {
            throw ConfigurationException::create("Projection '{$projectionName}' reads #[FromTaggedStream('{$tag}')] with #[Partitioned('{$partitioned->partitionHeaderName}')], but the partition of a tagged stream is the value of the tag '{$tag}', so a partition header has nothing to name. Use #[Partitioned] without a header name.");
        }

        if (! in_array($tag, $declaredTagNames, true)) {
            $declaredTagNames = array_unique($declaredTagNames);
            sort($declaredTagNames);
            throw ConfigurationException::create(sprintf(
                "Projection '%s' reads #[FromTaggedStream('%s')], but no event declares #[EventTag('%s')], so the projection would never see an event. Name a tag an event declares with #[EventTag] (declared tags: %s), or add #[EventTag('%s')] to the events the projection handles.",
                $projectionName,
                $tag,
                $tag,
                $declaredTagNames === [] ? 'none' : implode(', ', $declaredTagNames),
                $tag,
            ));
        }
    }

    /**
     * @param array<string, string> $tagsByProjectionName
     * @param string[] $filterOnlyTagNames
     */
    public static function assertNoPartitionByAFilterOnlyTag(array $tagsByProjectionName, array $filterOnlyTagNames): void
    {
        foreach ($tagsByProjectionName as $projectionName => $tag) {
            if (in_array($tag, $filterOnlyTagNames, true)) {
                throw ConfigurationException::create("Projection '{$projectionName}' reads #[FromTaggedStream('{$tag}')], but '{$tag}' is a filter-only tag: its events carry no tag sequence (it is always 0), so a partition of it has no position to resume from. Either remove '{$tag}' from DynamicConsistencyBoundaryConfiguration::withFilterOnlyTags(), or partition the projection by a counted tag.");
            }
        }
    }
}
