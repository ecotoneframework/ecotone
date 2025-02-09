<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\AggregateServiceBuilder;

use Ecotone\Modelling\Attribute\NamedEvent;

/**
 * licence Apache-2.0
 */
#[NamedEvent('something_was_created_private')]
final class SomethingWasCreatedPrivateEvent
{
    public function __construct(public int $somethingId)
    {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
        ];
    }
}
