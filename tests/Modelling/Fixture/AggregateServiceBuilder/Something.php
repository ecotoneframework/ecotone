<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\AggregateServiceBuilder;

use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\AggregateType;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\Modelling\WithAggregateVersioning;
use Ecotone\Api\Modelling\WithEvents;

#[AggregateType('something')]
#[Aggregate]
/**
 * licence Apache-2.0
 */
final class Something
{
    use WithAggregateVersioning;
    use WithEvents;

    public function __construct(#[Identifier] public int $int)
    {
        $this->recordThat(new SomethingWasCreatedPrivateEvent($int));
    }

    public function getAggregateVersion(): int
    {
        return $this->version;
    }
}
