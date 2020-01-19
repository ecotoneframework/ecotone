<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\Gateway;

use Ramsey\Uuid\Uuid;

interface UuidReturningGateway
{
    public function executeNoParameter() : Uuid;
}