<?php


namespace Test\Ecotone\Messaging\Fixture\Behat\InterceptedGateway;

use Ecotone\Messaging\Attribute\MessageGateway;
use Ecotone\Messaging\Attribute\MessageEndpoint;

interface CalculateGatewayExample
{
    #[MessageGateway(SomeQueryHandler::CALCULATE)]
    public function calculate(int $amount) : int;
}