<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\Retry;

use Ecotone\Api\Attribute\ErrorChannel;
use Ecotone\Api\Attribute\InstantRetry;
use Ecotone\Api\Gateway\CommandBus;

/**
 * licence Apache-2.0
 */
#[InstantRetry(retryTimes: 2)]
#[ErrorChannel('customErrorChannel')]
interface CommandBusWithErrorChannelAndInstantRetryAttribute extends CommandBus
{
}
