<?php

namespace Ecotone\Modelling;

use Ecotone\Modelling\Annotation\AggregateVersion;

trait WithVersioning
{
    #[AggregateVersion]
    private int $version = 0;
}