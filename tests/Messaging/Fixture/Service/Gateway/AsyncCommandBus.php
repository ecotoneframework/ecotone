<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Service\Gateway;

use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Modelling\CommandBus;

#[Asynchronous('async')]
/**
 * licence Apache-2.0
 */
interface AsyncCommandBus extends CommandBus
{
}
