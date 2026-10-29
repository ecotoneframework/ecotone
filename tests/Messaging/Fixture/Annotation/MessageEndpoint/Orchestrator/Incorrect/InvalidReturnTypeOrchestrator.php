<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Orchestrator\Incorrect;

use Ecotone\Messaging\Attribute\Orchestrator;
use stdClass;

/**
 * licence Enterprise
 */
class InvalidReturnTypeOrchestrator
{
    #[Orchestrator(inputChannelName: 'invalid.return.type')]
    public function invalidReturnType(): stdClass
    {
        return new stdClass();
    }

    #[Orchestrator(inputChannelName: 'single.step.as.string')]
    public function singleStepAsString(): string
    {
        return 'only_step';
    }
}
