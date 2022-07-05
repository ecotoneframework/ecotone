<?php

namespace Ecotone\Tests\Messaging\Fixture\Service;

class ServiceWithDefaultArgument
{
    public function execute(string $name = "") : void
    {

    }
}