<?php


namespace Test\Ecotone\Messaging\Fixture\Behat\GatewayInGateway;

use Ecotone\Messaging\Annotation\Gateway;
use Ecotone\Messaging\Annotation\MessageEndpoint;

/**
 * @MessageEndpoint()
 */
interface CalculateGatewayExample
{
    /**
     * @Gateway(requestChannel=SomeQueryHandler::CALCULATE,requiredInterceptorNames={InterceptorExample::class})
     */
    public function calculate(int $amount) : int;
}