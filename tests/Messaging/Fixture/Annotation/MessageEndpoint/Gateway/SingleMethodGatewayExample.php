<?php

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Gateway;

use Ecotone\Messaging\Attribute\MessageGateway;

/**
 * licence Apache-2.0
 */
interface SingleMethodGatewayExample
{
    #[MessageGateway('buy')]
    public function buy();
}
