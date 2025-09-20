<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Ecotone\Projecting;

class NullPartitionProvider implements PartitionProvider
{
    public function partitions(): iterable
    {
        yield null;
    }
}
