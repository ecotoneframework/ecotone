<?php

namespace FixtureAutoloadTest;

use Ecotone\Messaging\Annotation\ServiceContext;

class ApplicationContextExamplePSR0
{
    #[ServiceContext]
    public function doSomething()
    {

    }
}