<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Service;

use Ecotone\Modelling\Attribute\CommandHandler;
use stdClass;

class AggregateCommandHandlerWithInputChannelNameAndObject
{
    #[CommandHandler('execute', 'commandHandler')]
    public function execute(stdClass $class): int
    {
        return 1;
    }
}
