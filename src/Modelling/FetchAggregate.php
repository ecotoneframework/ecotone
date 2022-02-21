<?php

namespace Ecotone\Modelling;

class FetchAggregate
{
    public function fetch(?object $aggregate): ?object
    {
        return $aggregate;
    }
}