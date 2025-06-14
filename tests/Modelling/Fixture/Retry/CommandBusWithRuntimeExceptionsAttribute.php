<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\Retry;

use Ecotone\Modelling\Attribute\InstantRetry;
use Ecotone\Modelling\CommandBus;
use RuntimeException;

/**
 * licence Enterprise
 */
#[InstantRetry(retryTimes: 3, exceptions: [RuntimeException::class])]
interface CommandBusWithRuntimeExceptionsAttribute extends CommandBus
{
}
