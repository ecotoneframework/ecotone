<?php

namespace Fixture\Annotation\MessageEndpoint\Router;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Parameter\Payload;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Router;

/**
 * Class RouterWithNoResolutionRequiredExample
 * @package Fixture\Annotation\MessageEndpoint\Router
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpoint()
 */
class RouterWithNoResolutionRequiredExample
{
    /**
     * @param $content
     * @return string
     * @Router(endpointId="some-id", inputChannelName="inputChannel", isResolutionRequired=false, parameterConverters={
     *     @Payload(parameterName="content")
     * })
     */
    public function route($content) : string
    {
        return "outputChannel";
    }
}