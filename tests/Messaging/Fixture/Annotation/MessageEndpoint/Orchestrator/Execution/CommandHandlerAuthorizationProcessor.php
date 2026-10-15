<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Orchestrator\Execution;

use Ecotone\Api\Attribute\CommandHandler;

class CommandHandlerAuthorizationProcessor
{
    #[CommandHandler('execute.authorization', outputChannelName: 'start.authorization')]
    public function execute(string $data): string
    {
        return $data;
    }
}
