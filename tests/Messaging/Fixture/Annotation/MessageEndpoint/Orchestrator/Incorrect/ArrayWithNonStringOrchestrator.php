<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Orchestrator\Incorrect;

use Ecotone\Messaging\Attribute\Orchestrator;

/**
 * licence Enterprise
 */
class ArrayWithNonStringOrchestrator
{
    #[Orchestrator(inputChannelName: 'array.with.non.string')]
    public function arrayWithNonString(): array
    {
        return [1, 2, 3]; // integers instead of strings
    }
}
