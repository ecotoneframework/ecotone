<?php

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration\MessagingCommands;

use Ecotone\Messaging\Config\ConfiguredMessagingSystem;
use Ecotone\Messaging\Config\ConsoleCommandResultSet;

class MessagingBaseCommand
{
    public function executeConsoleCommand(string $commandName, array $parameters, ConfiguredMessagingSystem $configuredMessagingSystem) : void
    {
        $configuredMessagingSystem->runConsoleCommand($commandName, $parameters);
    }

    public function runAsynchronousEndpointCommand(string $consumerName, ConfiguredMessagingSystem $configuredMessagingSystem) : void
    {
        $configuredMessagingSystem->run($consumerName);
    }

    public function listAsynchronousEndpointsCommand(ConfiguredMessagingSystem $configuredMessagingSystem) : ConsoleCommandResultSet
    {
        $consumers = [];
        foreach ($configuredMessagingSystem->list() as $consumerName) {
            $consumers[] = [$consumerName];
        }

        return ConsoleCommandResultSet::create(["Name"], $consumers);
    }
}