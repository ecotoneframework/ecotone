<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\RoutingTest;

class RoutingTestHandler
{
    protected array $messages = [];

    public function getMessages(): array
    {
        return $this->messages;
    }

    public function clearMessages(): void
    {
        $this->messages = [];
    }
}
