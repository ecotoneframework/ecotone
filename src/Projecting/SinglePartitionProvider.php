<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Ecotone\Projecting;

class SinglePartitionProvider implements PartitionProvider
{
    public function count(StreamFilter $filter): int
    {
        return 1;
    }

    public function partitions(StreamFilter $filter, ?int $limit = null, int $offset = 0): iterable
    {
        // Global projection has a single null partition
        // If offset is 0 and limit allows at least 1, yield the single partition
        if ($offset === 0 && ($limit === null || $limit >= 1)) {
            yield null;
        }
    }
}
