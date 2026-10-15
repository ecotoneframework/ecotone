<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Orchestrator;

use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\Attribute\Orchestrator;

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
