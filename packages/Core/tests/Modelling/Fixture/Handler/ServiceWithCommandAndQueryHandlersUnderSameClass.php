<?php

namespace Test\Ecotone\Modelling\Fixture\Handler;

use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\QueryHandler;
use stdClass;

class ServiceWithCommandAndQueryHandlersUnderSameClass
{
    #[QueryHandler]
    public function execute1(stdClass $class): int
    {
    }

    #[CommandHandler]
    public function execute2(stdClass $class): int
    {
    }
}
