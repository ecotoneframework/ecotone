<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Orchestrator\Execution\Incorrect;

use Ecotone\Messaging\Attribute\OrchestratorGateway;

interface OrchestratorGatewayWithIncorrectRouting
{
    #[OrchestratorGateway]
    public function start(string $routing, mixed $data, array $metadata): string;
}
