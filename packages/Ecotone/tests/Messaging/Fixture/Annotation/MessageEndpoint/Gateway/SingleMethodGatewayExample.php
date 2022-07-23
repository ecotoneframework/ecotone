<?php

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Gateway;

use Ecotone\Messaging\Attribute\MessageGateway;

interface SingleMethodGatewayExample
{
    #[MessageGateway('buy')]
    public function buy();
}
