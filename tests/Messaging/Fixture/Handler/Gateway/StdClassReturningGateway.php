<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\Gateway;

use stdClass;

/**
 * licence Apache-2.0
 */
interface StdClassReturningGateway
{
    public function executeNoParameter(): stdClass;

    public function executeWithPayload(mixed $payload): stdClass;
}
