<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\CommandEventFlow;

final class RegisterUser
{
    public function __construct(public string $userId)
    {
    }
}