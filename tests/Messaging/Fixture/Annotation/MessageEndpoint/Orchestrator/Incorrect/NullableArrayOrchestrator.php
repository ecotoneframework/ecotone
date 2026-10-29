<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Orchestrator\Incorrect;

use Ecotone\Messaging\Attribute\Orchestrator;

/**
 * licence Enterprise
 */
class NullableArrayOrchestrator
{
    #[Orchestrator(inputChannelName: 'nullable.array')]
    public function nullableArray(): ?array
    {
        return ['step1', 'step2'];
    }
}
