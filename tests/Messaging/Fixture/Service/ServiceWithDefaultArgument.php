<?php

namespace Tests\Ecotone\Messaging\Fixture\Service;

class ServiceWithDefaultArgument
{
    public function execute(string $name = "") : void
    {

    }
}