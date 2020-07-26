<?php

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Router;

use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\Parameter\Payload;
use Ecotone\Messaging\Annotation\Router;

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