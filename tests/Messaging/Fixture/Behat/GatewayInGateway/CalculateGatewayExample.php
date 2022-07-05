<?php


namespace Ecotone\Tests\Messaging\Fixture\Behat\GatewayInGateway;

use Ecotone\Messaging\Attribute\MessageGateway;
use Ecotone\Messaging\Attribute\MessageEndpoint;

interface CalculateGatewayExample
{
    #[MessageGateway(SomeQueryHandler::CALCULATE, requiredInterceptorNames: [InterceptorExample::class])]
    public function calculate(int $amount) : int;
}