<?php

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Router;

use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\Parameter\Payload;
use Ecotone\Messaging\Annotation\Router;

class RouterWithNoResolutionRequiredExample
{
    #[Router("inputChannel", false)]
    public function route(#[Payload] $content) : string
    {
        return "outputChannel";
    }
}