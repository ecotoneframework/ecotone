<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\Gateway;

use stdClass;

interface StdClassReturningGateway
{
    public function executeNoParameter(): stdClass;

    public function executeWithPayload(mixed $payload): stdClass;
}
