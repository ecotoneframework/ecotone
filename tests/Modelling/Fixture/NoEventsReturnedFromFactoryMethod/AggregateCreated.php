<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\NoEventsReturnedFromFactoryMethod;

/**
 * licence Apache-2.0
 */
final class AggregateCreated
{
    public function __construct(public int $id)
    {
    }
}
