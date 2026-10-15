<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Orchestrator\Execution;

use Ecotone\Api\Attribute\OrchestratorGateway;

interface AuthorizationProcessGateway
{
    #[OrchestratorGateway]
    public function start(array $routing, mixed $data, array $metadata): string;
}
