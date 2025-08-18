<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Orchestrator;

use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Messaging\Attribute\InternalHandler;
use Ecotone\Messaging\Attribute\Orchestrator;

/**
 * licence Enterprise
 */
class AsynchronousOrchestrator
{
    private array $executedSteps = [];

    #[Asynchronous('async')]
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

    #[InternalHandler(inputChannelName: 'stepB')]
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

    public function getExecutedSteps(): array
    {
        return $this->executedSteps;
    }
}
