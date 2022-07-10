<?php


namespace Test\Ecotone\Messaging\Fixture\Behat\GatewayInGatewayWithMessages;

use Ecotone\Messaging\Attribute\MessageGateway;
use Ecotone\Messaging\Attribute\MessageEndpoint;
use Ecotone\Messaging\Message;

interface CalculateGatewayExampleWithMessages
{
    #[MessageGateway(SomeQueryHandler::CALCULATE, requiredInterceptorNames: [InterceptorExample::class])]
    public function calculate(Message $amount) : Message;
}