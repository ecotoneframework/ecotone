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
