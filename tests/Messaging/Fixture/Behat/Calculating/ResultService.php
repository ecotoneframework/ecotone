<?php

namespace Test\Ecotone\Messaging\Fixture\Behat\Calculating;

use Ecotone\Messaging\Attribute\MessageEndpoint;
use Ecotone\Messaging\Attribute\ServiceActivator;

class ResultService
{
    #[ServiceActivator("calculateChannel")]
    public function result(int $amount) : int
    {
        return $amount;
    }
}