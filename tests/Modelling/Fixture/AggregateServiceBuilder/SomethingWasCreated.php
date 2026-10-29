<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\AggregateServiceBuilder;

use Ecotone\Modelling\Attribute\NamedEvent;

/**
 * licence Apache-2.0
 */
#[NamedEvent('something_was_created')]
final class SomethingWasCreated
{
    public function __construct(public int $id, public int $somethingId)
    {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'somethingId' => $this->somethingId,
        ];
    }
}
