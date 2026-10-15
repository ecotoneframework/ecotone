<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate;

use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\IgnorePayload;
use stdClass;

#[Aggregate]
/**
 * licence Apache-2.0
 */
class ServiceCommandHandlerWithInputChannelNameAndIgnoreMessage
{
    #[CommandHandler('execute', 'commandHandler')]
    #[IgnorePayload]
    public function execute(stdClass $class): int
    {
        return 1;
    }
}
