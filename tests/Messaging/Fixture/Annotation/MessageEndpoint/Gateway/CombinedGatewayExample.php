<?php


namespace Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\MessageEndpoint\Gateway;

use SimplyCodedSoftware\Messaging\Annotation\Gateway\Gateway;
use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;

/**
 * Interface CombinedGateway
 * @package Fixture\Annotation\MessageEndpoint\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpoint()
 */
interface CombinedGatewayExample
{
    /**
     * @Gateway(requestChannel="buy")
     */
    public function buy() : void;

    /**
     * @Gateway(requestChannel="sell")
     */
    public function sell() : void;
}