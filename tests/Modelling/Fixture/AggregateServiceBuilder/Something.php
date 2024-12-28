<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\AggregateServiceBuilder;

use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\Identifier;
use Ecotone\Modelling\WithAggregateVersioning;

#[Aggregate]
/**
 * licence Apache-2.0
 */
final class Something
{
    use WithAggregateVersioning;

    public function __construct(#[Identifier] public int $int)
    {
    }

    public function getVersion(): int
    {
        return $this->version;
    }
}
