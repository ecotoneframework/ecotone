<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Ecotone\Projecting;

interface PartitionProvider
{
    /**
     * Returns the total count of partitions for the given stream filter.
     * For non-partitioned projections, returns 1.
     *
     * @param StreamFilter $filter The stream filter containing stream name and aggregate type
     * @return int Total number of partitions
     */
    public function count(StreamFilter $filter): int;

    /**
     * Returns partition keys for the projection based on the stream filter.
     * For non-partitioned projections, yields a single null value.
     *
     * @param StreamFilter $filter The stream filter containing stream name and aggregate type
     * @param int|null $limit Maximum number of partitions to return (null for unlimited)
     * @param int $offset Number of partitions to skip
     * @return iterable<string|null> Partition keys
     */
    public function partitions(StreamFilter $filter, ?int $limit = null, int $offset = 0): iterable;
}
