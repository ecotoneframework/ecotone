<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\CommandEventFlow;

/**
 * licence Apache-2.0
 */
final class RegisterUser
{
    public function __construct(public string $userId)
    {
    }
}
