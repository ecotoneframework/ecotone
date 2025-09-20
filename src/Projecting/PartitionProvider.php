<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Ecotone\Projecting;

interface PartitionProvider
{
    public function partitions(): iterable;
}
