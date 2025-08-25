<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Orchestrator;

use Ecotone\Messaging\Attribute\InternalHandler;
use Ecotone\Messaging\Attribute\Orchestrator;
use Ecotone\Messaging\Attribute\Splitter;

/**
 * licence Enterprise
 */
class OrchestratorWithSplitterStep
{
    private array $executedSteps = [];

    #[Orchestrator(inputChannelName: 'orchestrator.with.splitter', endpointId: 'splitter-orchestrator')]
    public function startWorkflow(): array
    {
        return ['prepare', 'split', 'process'];
    }

    #[InternalHandler(inputChannelName: 'prepare')]
    public function prepare(string $data): array
    {
        $this->executedSteps[] = 'prepare';
        // Create array data to be split
        return ['item1:' . $data, 'item2:' . $data, 'item3:' . $data];
    }

    #[Splitter(inputChannelName: 'split')]
    public function split(array $data): array
    {
        $this->executedSteps[] = 'split';
        // Return the array - Splitter will automatically split it into individual messages
        return $data;
    }

    #[InternalHandler(inputChannelName: 'process')]
    public function process(string $item): string
    {
        $this->executedSteps[] = 'processed_' . $item;
        return 'processed:' . $item;
    }

    public function getExecutedSteps(): array
    {
        return $this->executedSteps;
    }
}
