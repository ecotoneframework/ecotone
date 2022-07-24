<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\EventHandler;

use Ecotone\Modelling\Attribute\EventHandler;
use stdClass;

class ExampleEventHandlerWithServices
{
    #[EventHandler('someInput', 'some-id')]
    public function doSomething($command, stdClass $service1, stdClass $service2): void
    {
    }
}
