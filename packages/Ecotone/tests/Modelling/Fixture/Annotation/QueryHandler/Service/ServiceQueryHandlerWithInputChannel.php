<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler\Service;

use Ecotone\Modelling\Attribute\QueryHandler;

class ServiceQueryHandlerWithInputChannel
{
    #[QueryHandler('execute', 'queryHandler')]
    public function execute(): int
    {
    }
}
