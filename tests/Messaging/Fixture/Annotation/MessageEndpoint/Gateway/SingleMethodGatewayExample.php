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
interface SingleMethodGatewayExample
{
    /**
     * @MessageGateway(requestChannel="buy")
     */
    public function buy();
}