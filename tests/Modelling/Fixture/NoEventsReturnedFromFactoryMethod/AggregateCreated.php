<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\NoEventsReturnedFromFactoryMethod;

final class AggregateCreated
{
    public function __construct(public int $id)
    {
    }
}
