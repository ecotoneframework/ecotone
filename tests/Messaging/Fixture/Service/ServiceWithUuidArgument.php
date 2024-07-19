<?php

namespace Test\Ecotone\Messaging\Fixture\Service;

use Ramsey\Uuid\UuidInterface;

/**
 * licence Apache-2.0
 */
class ServiceWithUuidArgument
{
    public function execute(UuidInterface $uuid): void
    {
    }
}
