<?php

namespace Fixture\Annotation\MessageEndpoint\Gateway;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\GatewayAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageToParameter\MessageToPayloadParameterAnnotation;

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