<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\Gateway;

use Ramsey\Uuid\UuidInterface;

interface UuidReturningGateway
{
    public function executeNoParameter() : UuidInterface;
}