<?php

declare(strict_types=1);

namespace Ecotone\Tracing;

use function array_filter;
use function array_map;

use Ecotone\EventSourcing\Tagging\AggregateCounterTag;

use function implode;
use function sprintf;
use function str_starts_with;

/**
 * licence Apache-2.0
 */
final class DynamicConsistencyBoundarySpanAttributes
{
    public const MODELS = 'ecotone.dcb.models';

    public const TAGS = 'ecotone.dcb.tags';

    public const CAPTURED_VERSIONS = 'ecotone.dcb.captured_versions';

    public const AGGREGATES = 'ecotone.dcb.aggregates';

    public const EVENTS_FOLDED = 'ecotone.dcb.events_folded';

    public const EVENTS_APPENDED = 'ecotone.dcb.events_appended';

    public const CONFLICT_EVENT_NAME = 'dcb.conflict';

    /**
     * @param array<array{name: string, value: string, expectedVersion?: int}> $tagVersions
     */
    public static function userTags(array $tagVersions): string
    {
        return self::renderedList(array_filter($tagVersions, static fn (array $tagVersion): bool => ! self::isAggregateCounter($tagVersion)));
    }

    /**
     * @param array<array{name: string, value: string, expectedVersion?: int}> $tagVersions
     */
    public static function aggregates(array $tagVersions): string
    {
        return self::renderedList(array_filter($tagVersions, static fn (array $tagVersion): bool => self::isAggregateCounter($tagVersion)));
    }

    /**
     * @param array<array{name: string, value: string, expectedVersion: int}> $tagVersions
     */
    public static function capturedVersions(array $tagVersions): string
    {
        return implode(', ', array_map(
            static fn (array $tagVersion): string => sprintf('%s=%d', self::tagOf($tagVersion), $tagVersion['expectedVersion']),
            $tagVersions,
        ));
    }

    /**
     * @param array<array{name: string, value: string}> $tags
     */
    public static function tags(array $tags): string
    {
        return self::renderedList($tags);
    }

    /**
     * @param string[] $classNames
     */
    public static function classList(array $classNames): string
    {
        return implode(', ', $classNames);
    }

    /**
     * @param array<array{name: string, value: string}> $tags
     */
    private static function renderedList(array $tags): string
    {
        return implode(', ', array_map(static fn (array $tag): string => self::tagOf($tag), $tags));
    }

    /**
     * @param array{name: string, value: string} $tag
     */
    private static function tagOf(array $tag): string
    {
        return $tag['name'] . ':' . $tag['value'];
    }

    /**
     * @param array{name: string, value: string} $tag
     */
    private static function isAggregateCounter(array $tag): bool
    {
        return str_starts_with($tag['name'], AggregateCounterTag::NAME_PREFIX);
    }
}
