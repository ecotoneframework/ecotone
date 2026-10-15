<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Service;

use Ecotone\Api\Attribute\CommandHandler;

/**
 * licence Apache-2.0
 */
class CommandHandlerWithNoInputChannelName
{
    #[CommandHandler]
    public function noAction(): void
    {
    }
}
