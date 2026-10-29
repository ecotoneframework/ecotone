<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Orchestrator\Incorrect;

use Ecotone\Messaging\Attribute\Orchestrator;

/**
 * licence Enterprise
 */
class UnionTypeOrchestrator
{
    #[Orchestrator(inputChannelName: 'union.type')]
    public function unionType(): array|string
    {
        return ['step1', 'step2'];
    }
}
