<?php


namespace Test\Ecotone\Messaging\Fixture\Behat\GatewayInGatewayWithMessages;

use Ecotone\Messaging\Annotation\Gateway;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Message;

/**
 * @MessageEndpoint()
 */
interface CalculateGatewayExampleWithMessages
{
    /**
     * @Gateway(requestChannel=SomeQueryHandler::CALCULATE,requiredInterceptorNames={InterceptorExample::class})
     */
    public function calculate(Message $amount) : Message;
}