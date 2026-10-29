<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Orchestrator\Incorrect;

use Ecotone\Messaging\Attribute\Orchestrator;

/**
 * licence Enterprise
 */
class StringReturnTypeOrchestrator
{
    #[Orchestrator(inputChannelName: 'single.step.as.string')]
    public function singleStepAsString(): string
    {
        return 'only_step';
    }
}
