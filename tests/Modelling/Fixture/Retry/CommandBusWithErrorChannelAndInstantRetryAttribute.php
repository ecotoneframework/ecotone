<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\Retry;

use Ecotone\Messaging\Attribute\ErrorChannel;
use Ecotone\Modelling\Attribute\InstantRetry;
use Ecotone\Modelling\CommandBus;

/**
 * licence Apache-2.0
 */
#[InstantRetry(retryTimes: 2)]
#[ErrorChannel('customErrorChannel')]
interface CommandBusWithErrorChannelAndInstantRetryAttribute extends CommandBus
{
}
