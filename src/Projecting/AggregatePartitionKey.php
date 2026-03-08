<?php

/*
 * licence Enterprise
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
