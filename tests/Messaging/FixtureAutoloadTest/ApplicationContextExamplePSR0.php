<?php

namespace FixtureAutoloadTest;

use Ecotone\Messaging\Attribute\ServiceContext;

class ApplicationContextExamplePSR0
{
    #[ServiceContext]
    public function doSomething()
    {

    }
}