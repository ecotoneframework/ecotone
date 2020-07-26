<?php


namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Gateway;

use Ecotone\Messaging\Annotation\MessageGateway;
use Ecotone\Messaging\Annotation\MessageEndpoint;

interface SingleMethodGatewayExample
{
    /**
     * @MessageGateway(requestChannel="buy")
     */
    public function buy();
}