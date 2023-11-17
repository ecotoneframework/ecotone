<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\AggregateServiceBuilder;

final class CreateSomething
{
    public function __construct(public int $id, public int $somethingId)
    {
    }
}
