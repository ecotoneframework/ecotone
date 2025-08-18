<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Orchestrator\Execution;

final class AuthorizationStarted
{
    public function __construct(public string $data)
    {
    }
}
