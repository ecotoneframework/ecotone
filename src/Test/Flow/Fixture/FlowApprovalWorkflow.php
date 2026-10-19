<?php

declare(strict_types=1);

namespace Ecotone\Test\Flow\Fixture;

use Ecotone\Api\Attribute\OrchestratorGateway;

/**
 * licence Apache-2.0
 */
interface FlowApprovalWorkflow
{
    #[OrchestratorGateway]
    public function start(array $routing, mixed $data): mixed;
}
