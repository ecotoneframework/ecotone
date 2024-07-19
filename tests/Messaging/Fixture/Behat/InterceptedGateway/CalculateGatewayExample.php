<?php

namespace Test\Ecotone\Messaging\Fixture\Behat\InterceptedGateway;

use Ecotone\Messaging\Attribute\MessageGateway;

/**
 * licence Apache-2.0
 */
interface CalculateGatewayExample
{
    #[MessageGateway(SomeQueryHandler::CALCULATE)]
    public function calculate(int $amount): int;
}
