<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Orchestrator;

use Ecotone\Messaging\Attribute\InternalHandler;
use Ecotone\Messaging\Attribute\Orchestrator;
use Ecotone\Messaging\Attribute\Splitter;

/**
 * licence Enterprise
 */
class OrchestratorWithSplitterOutputChannel
{
    private array $executedSteps = [];

    #[Orchestrator(inputChannelName: 'orchestrator.with.splitter.output', endpointId: 'splitter-output-orchestrator')]
    public function startWorkflow(): array
    {
        return ['prepare', 'split', 'finalize'];
    }

    #[InternalHandler(inputChannelName: 'prepare')]
    public function prepare(string $data): array
    {
        $this->executedSteps[] = 'prepare';
        // Create array data to be split
        return ['data1:' . $data, 'data2:' . $data, 'data3:' . $data];
    }

    #[Splitter(inputChannelName: 'split', outputChannelName: 'transform')]
    public function split(array $data): array
    {
        $this->executedSteps[] = 'split';
        // Return the array - Splitter will send each item to 'transform' channel
        return $data;
    }

    #[InternalHandler(inputChannelName: 'transform', outputChannelName: 'finalize')]
    public function transform(string $item): string
    {
        return 'transformed:' . $item;
    }

    #[InternalHandler(inputChannelName: 'finalize')]
    public function finalize(string $item): void
    {
        $this->executedSteps[] = 'finalized:' . $item;
    }

    public function getExecutedSteps(): array
    {
        return $this->executedSteps;
    }
}
