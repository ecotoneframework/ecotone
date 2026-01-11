<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Ecotone\Projecting;

use function in_array;

class SinglePartitionProvider implements PartitionProvider
{
    /**
     * @param string[] $nonPartitionedProjectionNames
     */
    public function __construct(
        private array $nonPartitionedProjectionNames,
    ) {
    }

    public function canHandle(string $projectionName): bool
    {
        return in_array($projectionName, $this->nonPartitionedProjectionNames, true);
    }

    public function count(StreamFilter $filter): int
    {
        return 1;
    }

    public function partitions(StreamFilter $filter, ?int $limit = null, int $offset = 0): iterable
    {
        if ($offset === 0 && ($limit === null || $limit >= 1)) {
            yield null;
        }
    }
}
