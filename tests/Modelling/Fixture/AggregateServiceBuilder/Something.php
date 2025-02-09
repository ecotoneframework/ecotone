<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\AggregateServiceBuilder;

use Ecotone\EventSourcing\Attribute\AggregateType;
use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\Identifier;
use Ecotone\Modelling\WithAggregateVersioning;
use Ecotone\Modelling\WithEvents;

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

    public function getVersion(): int
    {
        return $this->version;
    }
}
