<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Orchestrator;

use Ecotone\Messaging\Attribute\InternalHandler;
use Ecotone\Messaging\Attribute\Orchestrator;
use Ecotone\Messaging\Attribute\Parameter\Reference;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\CommandBus;

/**
 * licence Enterprise
 */
class OrchestratorWithInternalBus
{
    private array $executedSteps = [];

    #[Orchestrator(inputChannelName: 'orchestrator.ending.during.flow')]
    public function simpleWorkflow(): array
    {
        return ['stepA', 'stepB', 'commandBusAction', 'stepC'];
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

    #[InternalHandler(inputChannelName: 'commandBusAction')]
    public function internalWorkflow(#[Reference] CommandBus $commandBus): array
    {
        return $commandBus->sendWithRouting('commandBusAction.execute', []);
    }

    #[CommandHandler('commandBusAction.execute')]
    public function execute(array $data): array
    {
        $this->executedSteps[] = 'commandBusAction.execute';
        $data[] = 'commandBusAction.execute';

        return $data;
    }

    public function getExecutedSteps(): array
    {
        return $this->executedSteps;
    }
}
