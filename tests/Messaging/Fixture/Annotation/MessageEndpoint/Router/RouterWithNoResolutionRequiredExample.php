<?php

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\MessageEndpoint\Router;

use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\Messaging\Annotation\Parameter\Payload;
use SimplyCodedSoftware\Messaging\Annotation\Router;

/**
 * Class RouterWithNoResolutionRequiredExample
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\MessageEndpoint\Router
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