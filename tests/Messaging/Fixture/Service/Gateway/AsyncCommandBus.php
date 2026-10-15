<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Service\Gateway;

use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Gateway\CommandBus;

#[Asynchronous('async')]
/**
 * licence Apache-2.0
 */
interface AsyncCommandBus extends CommandBus
{
}
