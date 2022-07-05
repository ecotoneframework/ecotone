<?php

namespace Ecotone\Modelling;

use Ecotone\Modelling\Attribute\AggregateVersion;

trait WithAggregateVersioning
{
    #[AggregateVersion]
    private int $version = 0;
}