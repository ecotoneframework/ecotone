<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Orchestrator\Execution\Incorrect;

use Ecotone\Api\Attribute\OrchestratorGateway;

interface OrchestratorGatewayWithIncorrectMetadata
{
    #[OrchestratorGateway]
    public function start(array $routing, mixed $data, string $metadata): string;
}
