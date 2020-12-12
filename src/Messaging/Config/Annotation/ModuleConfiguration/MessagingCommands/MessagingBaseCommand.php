<?php

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration\MessagingCommands;

use Ecotone\Messaging\Config\ConfiguredMessagingSystem;
use Ecotone\Messaging\Config\ConsoleCommandResultSet;

class MessagingBaseCommand
{
    public function runAsynchronousEndpoint(string $consumerName, ConfiguredMessagingSystem $configuredMessagingSystem) : void
    {
        $configuredMessagingSystem->runAsynchronouslyRunningEndpoint($consumerName);
    }

    public function listAsynchronousEndpoints(ConfiguredMessagingSystem $configuredMessagingSystem) : ConsoleCommandResultSet
    {
        $consumers = [];
        foreach ($configuredMessagingSystem->getListOfAsynchronouslyRunningConsumers() as $consumerName) {
            $consumers[] = [$consumerName];
        }

        return ConsoleCommandResultSet::create(["Name"], $consumers);
    }
}