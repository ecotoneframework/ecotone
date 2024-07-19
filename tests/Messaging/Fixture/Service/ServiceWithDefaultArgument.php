<?php

namespace Test\Ecotone\Messaging\Fixture\Service;

/**
 * licence Apache-2.0
 */
class ServiceWithDefaultArgument
{
    public function execute(string $name = ''): void
    {
    }
}
