<?php

namespace Test\Ecotone\Messaging\Fixture\Behat\InterceptedScheduled;

use Ecotone\Messaging\Attribute\MessageGateway;

/**
 * licence Apache-2.0
 */
interface InterceptedScheduledGateway
{
    #[MessageGateway('getRequestedData')]
    public function getInterceptedData(): int;
}
