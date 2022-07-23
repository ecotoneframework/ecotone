<?php

namespace Test\Ecotone\Messaging\Fixture\Behat\InterceptedGateway;

use Ecotone\Messaging\Attribute\MessageGateway;

interface CalculateGatewayExample
{
    #[MessageGateway(SomeQueryHandler::CALCULATE)]
    public function calculate(int $amount): int;
}
