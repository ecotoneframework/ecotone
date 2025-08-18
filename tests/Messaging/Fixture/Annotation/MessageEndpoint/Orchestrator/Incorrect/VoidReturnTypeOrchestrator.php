<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Orchestrator\Incorrect;

use Ecotone\Messaging\Attribute\Orchestrator;

/**
 * licence Enterprise
 */
class VoidReturnTypeOrchestrator
{
    #[Orchestrator(inputChannelName: 'void.return.type')]
    public function voidReturnType(): void
    {
        // void return type
    }
}
