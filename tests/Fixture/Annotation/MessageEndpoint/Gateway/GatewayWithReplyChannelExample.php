<?php

namespace Fixture\Annotation\MessageEndpoint\Gateway;

use SimplyCodedSoftware\Messaging\Annotation\GatewayAnnotation;
use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\Messaging\Annotation\ParameterConverter\PayloadParameterConverterAnnotation;

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
     * @GatewayAnnotation(requestChannel="requestChannel", replyChannel="replyChannel", parameterConverters={
     *  @PayloadParameterConverterAnnotation(parameterName="orderId")
     * })
     */
    public function buy(string $orderId) : bool;
}