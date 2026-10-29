<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Orchestrator\Execution;

use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Modelling\Attribute\EventHandler;

class AsynchronousEventHandlerAuthorizationProcessor
{
    #[Asynchronous('async')]
    #[EventHandler(endpointId: 'async-authorization', outputChannelName: 'start.authorization')]
    public function execute(AuthorizationStarted $data): string
    {
        return $data->data;
    }
}
