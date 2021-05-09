<?php


namespace Test\Ecotone\Messaging\Fixture\Service;

use Ramsey\Uuid\UuidInterface;

class ServiceWithUuidArgument
{
    public function execute(UuidInterface $uuid) : void
    {

    }
}