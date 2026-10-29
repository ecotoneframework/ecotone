<?php

namespace Ecotone\Modelling;

use Ecotone\Modelling\Attribute\AggregateVersion;

/**
 * licence Apache-2.0
 */
trait WithAggregateVersioning
{
    #[AggregateVersion]
    private int $version = 0;
}
