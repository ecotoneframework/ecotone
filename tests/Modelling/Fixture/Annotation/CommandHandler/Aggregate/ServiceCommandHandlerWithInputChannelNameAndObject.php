<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate;

use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\CommandHandler;
use stdClass;

#[Aggregate]
/**
 * licence Apache-2.0
 */
class ServiceCommandHandlerWithInputChannelNameAndObject
{
    #[CommandHandler('execute', 'commandHandler')]
    public function execute(stdClass $class): int
    {
        return 1;
    }
}
