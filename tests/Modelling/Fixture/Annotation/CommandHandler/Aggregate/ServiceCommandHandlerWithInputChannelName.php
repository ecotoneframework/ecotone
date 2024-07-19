<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate;

use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\CommandHandler;

#[Aggregate]
/**
 * licence Apache-2.0
 */
class ServiceCommandHandlerWithInputChannelName
{
    #[CommandHandler('execute', 'commandHandler')]
    public function execute(): int
    {
        return 1;
    }
}
