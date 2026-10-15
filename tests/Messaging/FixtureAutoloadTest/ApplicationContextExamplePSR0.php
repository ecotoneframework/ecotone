<?php

namespace FixtureAutoloadTest;

use Ecotone\Api\Attribute\ServiceContext;

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
