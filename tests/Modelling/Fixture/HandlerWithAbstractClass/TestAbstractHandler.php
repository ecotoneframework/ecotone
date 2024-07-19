<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\HandlerWithAbstractClass;

use Ecotone\Modelling\Attribute\CommandHandler;

/**
 * licence Apache-2.0
 */
abstract class TestAbstractHandler
{
    #[CommandHandler]
    public function execute(TestCommand $command): int
    {
        return $command->amount;
    }
}
