<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Service;

use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\IgnorePayload;
use stdClass;

/**
 * licence Apache-2.0
 */
class AggregateCommandHandlerWithInputChannelNameAndIgnoreMessage
{
    #[CommandHandler('execute', 'commandHandler')]
    #[IgnorePayload]
    public function execute(stdClass $class): int
    {
        return 1;
    }
}
