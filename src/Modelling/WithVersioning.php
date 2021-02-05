<?php

namespace Ecotone\Modelling;

use Ecotone\Modelling\Attribute\AggregateVersion;

trait WithVersioning
{
    #[AggregateVersion]
    private int $version = 0;
}