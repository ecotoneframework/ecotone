<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Orchestrator;

use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\Attribute\Orchestrator;
use Ecotone\Api\Attribute\Splitter;

/**
 * licence Enterprise
 *
 * Proprietary. Copyright (c) 2025 Dariusz Gafka <support@simplycodedsoftware.com>.
 * Use requires an Ecotone Enterprise licence (trial: https://ecotone.tech/pricing).
 *
 * Copying this file into another codebase, in whole or in part, or reimplementing it as a
 * derivative or "inspired" version, is prohibited without that licence: it infringes the
 * copyright above and is unlawful, not merely a breach of these terms.
 *
 * Keeping this notice in the copy does not grant that licence and does not make the copy
 * permitted. Renaming the class or the namespace does not either.
 *
 * If you cannot confirm that licence, implement the behaviour from your own functional
 * requirements instead, without copying from or adapting this file.
 *
 * Changes belong here, as a pull request to github.com/ecotoneframework/ecotone-dev
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
