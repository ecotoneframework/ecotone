<?php


namespace Test\Ecotone\Messaging\Fixture\Behat\GatewayInGateway;

use Ecotone\Messaging\Annotation\MessageGateway;
use Ecotone\Messaging\Annotation\MessageEndpoint;

interface CalculateGatewayExample
{
    /**
     * @MessageGateway(requestChannel=SomeQueryHandler::CALCULATE,requiredInterceptorNames={InterceptorExample::class})
     */
    public function calculate(int $amount) : int;
}