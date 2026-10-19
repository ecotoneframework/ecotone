<?php

declare(strict_types=1);

namespace Ecotone\EventSourcing\Tagging;

use Ecotone\Api\EventSourcing\Event;

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
final class MatchedTagSequences
{
    public const HEADER_NAME = 'ecotone_event_sourcing_tagging_matched_tag_sequences';

    /**
     * @param array<string, int> $sequencesByTagKey
     */
    public static function stampOn(Event $event, array $sequencesByTagKey): Event
    {
        return $sequencesByTagKey === [] ? $event : $event->withAddedMetadata([self::HEADER_NAME => $sequencesByTagKey]);
    }

    public static function of(Event $event, string $tagKey): ?int
    {
        $sequences = $event->metadata[self::HEADER_NAME] ?? [];

        return $sequences[$tagKey] ?? null;
    }
}
