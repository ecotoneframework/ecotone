<?php

namespace Test\Ecotone\Messaging\Fixture\Behat\Calculating;

use Ecotone\Api\Attribute\InternalHandler;

/**
 * licence Apache-2.0
 */
class ResultService
{
    #[InternalHandler('calculateChannel')]
    public function result(int $amount): int
    {
        return $amount;
    }
}
