<?php


namespace Test\Ecotone\Messaging\Fixture\Service;


use Ramsey\Uuid\Uuid;

class ServiceWithUuidArgument
{
    public function execute(Uuid $uuid) : void
    {

    }
}