<?php

namespace Ecotone\Modelling;

/**
 * licence Apache-2.0
 */
class FetchAggregate
{
    public function fetch(?object $aggregate): ?object
    {
        return $aggregate;
    }
}
