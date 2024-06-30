<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\Gateway;

interface MixedReturningGateway
{
    public function executeNoParameter(): mixed;

    /**
     * @return int[]
     */
    public function executeWithPayload(mixed $payload): array;
}
