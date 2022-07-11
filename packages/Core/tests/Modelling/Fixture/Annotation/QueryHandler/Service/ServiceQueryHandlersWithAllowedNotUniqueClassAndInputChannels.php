<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler\Service;

use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\NotUniqueHandler;
use stdClass;

class ServiceQueryHandlersWithAllowedNotUniqueClassAndInputChannels
{
    #[CommandHandler('some1')]
    #[NotUniqueHandler]
    public function execute1(stdClass $class): int
    {
    }

    #[CommandHandler('some2')]
    #[NotUniqueHandler]
    public function execute2(stdClass $class): int
    {
    }
}
