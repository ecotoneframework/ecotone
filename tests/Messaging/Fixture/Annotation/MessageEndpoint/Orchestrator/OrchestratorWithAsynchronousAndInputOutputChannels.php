<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Orchestrator;

use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Messaging\Attribute\InternalHandler;
use Ecotone\Messaging\Attribute\Orchestrator;

/**
 * licence Enterprise
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
    #[InternalHandler(inputChannelName: 'stepE')]
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
