<?php

namespace Test\Ecotone\Messaging\Fixture\Behat\Calculating;

use Ecotone\Messaging\Attribute\ServiceActivator;

/**
 * licence Apache-2.0
 */
class ResultService
{
    #[ServiceActivator('calculateChannel')]
    public function result(int $amount): int
    {
        return $amount;
    }
}
