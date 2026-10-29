<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\EventHandler;

use Ecotone\Modelling\Attribute\EventHandler;

/**
 * licence Apache-2.0
 */
class ExampleEventEventHandler
{
    #[EventHandler('someInput', 'some-id')]
    public function doSomething(): void
    {
    }
}
