<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Orchestrator;

use Ecotone\Messaging\Attribute\InternalHandler;
use Ecotone\Messaging\Attribute\Orchestrator;

/**
 * licence Enterprise
 */
class OrchestratorEndingDuringFlow
{
    private array $executedSteps = [];

    #[Orchestrator(inputChannelName: 'orchestrator.ending.during.flow')]
    public function simpleWorkflow(): array
    {
        return ['step1', 'step2', 'step3'];
    }

    #[InternalHandler(inputChannelName: 'step1')]
    public function step1(): string
    {
        $this->executedSteps[] = 'step1';
        return 'step1';
    }

    #[InternalHandler(inputChannelName: 'step2')]
    public function step2(): ?string
    {
        $this->executedSteps[] = 'step2';
        return null;
    }

    #[InternalHandler(inputChannelName: 'step3')]
    public function step3(): string
    {
        $this->executedSteps[] = 'step3';
        return 'step3';
    }

    public function getExecutedSteps(): array
    {
        return $this->executedSteps;
    }
}
