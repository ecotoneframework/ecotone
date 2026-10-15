<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Orchestrator;

use Ecotone\Api\Attribute\Asynchronous;
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
class OrchestratorWithAsynchronousAndInputOutputChannels
{
    private array $executedSteps = [];

    #[Orchestrator(inputChannelName: 'asynchronous.workflow', endpointId: 'async-orchestrator')]
    public function simpleWorkflow(): array
    {
        return ['stepA', 'stepB', 'stepC'];
    }

    #[InternalHandler(inputChannelName: 'stepA')]
    public function stepA(array $data): array
    {
        $this->executedSteps[] = 'stepA';
        $data[] = 'stepA';

        return $data;
    }

    #[Asynchronous('async')]
    #[InternalHandler(inputChannelName: 'stepB', outputChannelName: 'stepD', endpointId: 'async-step')]
    public function stepB(array $data): array
    {
        $this->executedSteps[] = 'stepB';
        $data[] = 'stepB';

        return $data;
    }

    #[InternalHandler(inputChannelName: 'stepC')]
    public function stepC(): array
    {
        $this->executedSteps[] = 'stepC';
        $data[] = 'stepC';

        return $data;
    }

    #[Asynchronous('async')]
    #[Orchestrator(inputChannelName: 'stepD')]
    public function stepD(): array
    {
        $this->executedSteps[] = 'stepD';
        $data[] = 'stepD';

        return ['stepE'];
    }

    #[Asynchronous('async')]
    #[InternalHandler(inputChannelName: 'stepE', endpointId: 'step-e')]
    public function stepE(): array
    {
        $this->executedSteps[] = 'stepE';
        $data[] = 'stepE';

        return $data;
    }

    public function getExecutedSteps(): array
    {
        return $this->executedSteps;
    }
}
