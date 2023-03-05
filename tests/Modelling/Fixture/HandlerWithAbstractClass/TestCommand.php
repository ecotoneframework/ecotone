<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\HandlerWithAbstractClass;

final class TestCommand
{
    public function __construct(public int $amount)
    {

    }
}