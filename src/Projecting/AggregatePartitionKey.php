<?php

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
declare(strict_types=1);

namespace Ecotone\Projecting;

class AggregatePartitionKey
{
    public static function compose(string $streamName, string $aggregateType, string $aggregateId): string
    {
        return "{$streamName}:{$aggregateType}:{$aggregateId}";
    }

    /**
     * @return array{streamName: string, aggregateType: string, aggregateId: string}|null
     */
    public static function decompose(string $partitionKey): ?array
    {
        $parts = explode(':', $partitionKey, 3);
        if (count($parts) !== 3) {
            return null;
        }

        return [
            'streamName' => $parts[0],
            'aggregateType' => $parts[1],
            'aggregateId' => $parts[2],
        ];
    }
}
