<?php

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration\MessagingCommands;

use Ecotone\Messaging\Config\ConfiguredMessagingSystem;
use Ecotone\Messaging\Config\ConsoleCommandResultSet;

class MessagingBaseCommand
{
    public function runAsynchronousEndpoint(string $consumerName, ConfiguredMessagingSystem $configuredMessagingSystem) : void
    {
        $configuredMessagingSystem->runSeparatelyRunningEndpointBy($consumerName);
    }

    public function listAsynchronousEndpoints(ConfiguredMessagingSystem $configuredMessagingSystem) : ConsoleCommandResultSet
    {
        $consumers = [];
        foreach ($configuredMessagingSystem->getListOfSeparatelyRunningConsumers() as $consumerName) {
            $consumers[] = [$consumerName];
        }

        return ConsoleCommandResultSet::create(["Name"], $consumers);
    }
}