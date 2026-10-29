<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\Retry;

use Ecotone\Modelling\Attribute\InstantRetry;
use Ecotone\Modelling\CommandBus;

/**
 * licence Enterprise
 */
#[InstantRetry(retryTimes: 2)]
interface CommandBusWithCustomRetryCountAttribute extends CommandBus
{
}
