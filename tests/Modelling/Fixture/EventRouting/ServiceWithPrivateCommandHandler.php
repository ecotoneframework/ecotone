<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\EventRouting;

use Ecotone\Modelling\Attribute\CommandHandler;

/**
 * licence Apache-2.0
 */
final class ServiceWithPrivateCommandHandler
{
    #[CommandHandler]
    private function handle(PlaceOrder $command): void
    {
    }
}
