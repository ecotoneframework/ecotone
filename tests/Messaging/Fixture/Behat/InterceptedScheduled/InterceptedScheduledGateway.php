<?php

namespace Tests\Ecotone\Messaging\Fixture\Behat\InterceptedScheduled;

use Ecotone\Messaging\Attribute\MessageGateway;

interface InterceptedScheduledGateway
{
    #[MessageGateway("getRequestedData")]
    public function getInterceptedData() : int;
}