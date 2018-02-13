<?php

namespace Fixture\Annotation\MessageEndpoint\Gateway;

use SimplyCodedSoftware\Messaging\Annotation\GatewayAnnotation;
use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\Messaging\Annotation\MessageToParameter\MessageToPayloadParameterAnnotation;

/**
 * Class GatewayWithReplyChannelExample
 * @package Fixture\Annotation\MessageEndpoint\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpoint()
 */
interface GatewayWithReplyChannelExample
{
    /**
     * @param string $orderId
     * @return bool
     *
     * @GatewayAnnotation(requestChannel="requestChannel", parameterConverters={
     *  @MessageToPayloadParameterAnnotation(parameterName="orderId")
     * })
     */
    public function buy(string $orderId) : bool;
}