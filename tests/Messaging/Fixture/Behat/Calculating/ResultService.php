<?php

namespace Test\Ecotone\Messaging\Fixture\Behat\Calculating;

use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\ServiceActivator;

class ResultService
{
    /**
     * @param int $amount
     * @return int
     * @ServiceActivator(inputChannelName="calculateChannel")
     */
    public function result(int $amount) : int
    {
        return $amount;
    }
}