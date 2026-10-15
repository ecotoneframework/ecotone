<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Service;

use Ecotone\Api\Attribute\CommandHandler;

/**
 * licence Apache-2.0
 */
class AggregateCommandHandlerWithInputChannelName
{
    #[CommandHandler('execute', 'commandHandler')]
    public function execute(): int
    {
        return 1;
    }
}
