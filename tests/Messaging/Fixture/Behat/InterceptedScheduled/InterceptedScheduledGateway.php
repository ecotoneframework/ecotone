<?php

namespace Ecotone\Tests\Messaging\Fixture\Behat\InterceptedScheduled;

use Ecotone\Messaging\Attribute\MessageGateway;

interface InterceptedScheduledGateway
{
    #[MessageGateway("getRequestedData")]
    public function getInterceptedData() : int;
}