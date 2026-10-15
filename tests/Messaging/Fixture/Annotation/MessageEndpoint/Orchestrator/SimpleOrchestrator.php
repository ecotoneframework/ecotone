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
class SimpleOrchestrator
{
    private array $executedSteps = [];

    #[Orchestrator(inputChannelName: 'simple.workflow')]
    public function simpleWorkflow(): array
    {
        return ['step1', 'step2', 'step3'];
    }

    #[Orchestrator(inputChannelName: 'empty.workflow')]
    public function emptyWorkflow(): array
    {
        return [];
    }

    #[Orchestrator(inputChannelName: 'single.step')]
    public function singleStep(): array
    {
        return ['only_step'];
    }

    #[InternalHandler(inputChannelName: 'validate')]
    public function validate(string $data): string
    {
        $this->executedSteps[] = 'validate';
        return 'validated: ' . $data;
    }

    #[InternalHandler(inputChannelName: 'process')]
    public function process(string $data): string
    {
        $this->executedSteps[] = 'process';
        return 'processed: ' . $data;
    }

    #[InternalHandler(inputChannelName: 'sendEmail')]
    public function sendEmail(string $data): string
    {
        $this->executedSteps[] = 'sendEmail';
        return 'email sent for: ' . $data;
    }

    #[InternalHandler(inputChannelName: 'only_step')]
    public function onlyStep(string $data): string
    {
        $this->executedSteps[] = 'only_step';
        return 'validated: ' . $data;
    }

    public function getExecutedSteps(): array
    {
        return $this->executedSteps;
    }
}
