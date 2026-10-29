<?php

namespace FixtureAutoloadTest;

use Ecotone\Messaging\Attribute\ServiceContext;

/**
 * licence Apache-2.0
 */
class ApplicationContextExamplePSR0
{
    #[ServiceContext]
    public function doSomething()
    {
    }
}
