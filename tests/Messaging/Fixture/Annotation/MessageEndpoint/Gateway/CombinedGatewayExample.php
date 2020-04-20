<?php


namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Gateway;

use Ecotone\Messaging\Annotation\MessageGateway;
use Ecotone\Messaging\Annotation\MessageEndpoint;

/**
 * Interface CombinedGateway
 * @package Fixture\Annotation\MessageEndpoint\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpoint()
 */
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