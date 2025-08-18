<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Orchestrator;

use Ecotone\Messaging\Attribute\InternalHandler;
use Ecotone\Messaging\Attribute\Orchestrator;

/**
 * licence Enterprise
 */
class AuthorizationOrchestrator
{
    private array $executedSteps = [];

    #[Orchestrator(inputChannelName: 'start.authorization', endpointId: 'auth-orchestrator')]
    public function startAuthorization(): array
    {
        return ['validate', 'process', 'sendEmail'];
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

    public function getExecutedSteps(): array
    {
        return $this->executedSteps;
    }
}
