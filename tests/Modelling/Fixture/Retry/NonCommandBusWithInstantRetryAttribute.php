<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\Retry;

use Ecotone\Modelling\Attribute\InstantRetry;

/**
 * licence Enterprise
 */
#[InstantRetry(retryTimes: 3)]
interface NonCommandBusWithInstantRetryAttribute
{
    public function execute(string $command): void;
}
