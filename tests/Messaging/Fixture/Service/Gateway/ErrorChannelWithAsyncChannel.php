<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Service\Gateway;

use Ecotone\Api\Attribute\ErrorChannel;
use Ecotone\Api\Gateway\CommandBus;

#[ErrorChannel('async')]
interface ErrorChannelWithAsyncChannel extends CommandBus
{
}
