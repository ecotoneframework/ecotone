<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\Gateway;

/**
 * licence Apache-2.0
 */
interface MixedReturningGateway
{
    public function executeNoParameter(): mixed;

    /**
     * @return int[]
     */
    public function executeWithPayload(mixed $payload): array;
}
