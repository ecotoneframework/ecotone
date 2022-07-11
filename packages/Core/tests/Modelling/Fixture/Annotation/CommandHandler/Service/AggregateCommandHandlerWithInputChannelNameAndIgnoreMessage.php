<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Service;

use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\IgnorePayload;
use stdClass;

class AggregateCommandHandlerWithInputChannelNameAndIgnoreMessage
{
    #[CommandHandler('execute', 'commandHandler')]
    #[IgnorePayload]
    public function execute(stdClass $class): int
    {
        return 1;
    }
}
