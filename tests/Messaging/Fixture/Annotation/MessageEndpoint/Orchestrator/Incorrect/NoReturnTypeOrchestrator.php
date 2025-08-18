<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Orchestrator\Incorrect;

use Ecotone\Messaging\Attribute\Orchestrator;

/**
 * licence Enterprise
 */
class NoReturnTypeOrchestrator
{
    #[Orchestrator(inputChannelName: 'no.return.type')]
    public function noReturnType()
    {
        return ['step1', 'step2'];
    }
}
