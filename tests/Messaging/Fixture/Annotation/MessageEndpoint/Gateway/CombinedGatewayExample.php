<?php


namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Gateway;

use Ecotone\Messaging\Annotation\MessageGateway;
use Ecotone\Messaging\Annotation\MessageEndpoint;

interface CombinedGatewayExample
{
    /**
     * @MessageGateway(requestChannel="buy")
     */
    public function buy() : void;

    /**
     * @MessageGateway(requestChannel="sell")
     */
    public function sell() : void;
}