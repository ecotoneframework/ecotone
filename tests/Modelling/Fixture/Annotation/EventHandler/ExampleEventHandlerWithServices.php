<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\EventHandler;

use Ecotone\Api\Attribute\EventHandler;
use stdClass;

/**
 * licence Apache-2.0
 */
class ExampleEventHandlerWithServices
{
    #[EventHandler('someInput', 'some-id')]
    public function doSomething($command, stdClass $service1, stdClass $service2): void
    {
    }
}
