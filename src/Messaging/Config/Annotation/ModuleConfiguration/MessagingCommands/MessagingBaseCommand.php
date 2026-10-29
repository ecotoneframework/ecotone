<?php

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration\MessagingCommands;

use Ecotone\Messaging\Attribute\ConsoleParameterOption;
use Ecotone\Messaging\Config\ConfiguredMessagingSystem;
use Ecotone\Messaging\Config\ConsoleCommandResultSet;
use Ecotone\Messaging\Endpoint\ExecutionPollingMetadata;

/**
 * licence Apache-2.0
 */
class MessagingBaseCommand
{
    public function executeConsoleCommand(string $commandName, array $parameters, ConfiguredMessagingSystem $configuredMessagingSystem): mixed
    {
        return $configuredMessagingSystem->runConsoleCommand($commandName, $parameters);
    }

    public function runAsynchronousEndpointCommand(string $consumerName, ConfiguredMessagingSystem $configuredMessagingSystem, #[ConsoleParameterOption] ?string $handledMessageLimit = null, #[ConsoleParameterOption] ?int $executionTimeLimit = null, #[ConsoleParameterOption] ?int $memoryLimit = null, #[ConsoleParameterOption] ?string $cron = null, #[ConsoleParameterOption] bool $stopOnFailure = false, #[ConsoleParameterOption] bool $finishWhenNoMessages = false): void
    {
        $pollingMetadata = ExecutionPollingMetadata::createWithDefaults();
        if ($stopOnFailure) {
            $pollingMetadata = $pollingMetadata->withStopOnError(true);
        }
        if ($handledMessageLimit) {
            $pollingMetadata = $pollingMetadata->withHandledMessageLimit($handledMessageLimit);
        }
        if ($executionTimeLimit) {
            $pollingMetadata = $pollingMetadata->withExecutionTimeLimitInMilliseconds($executionTimeLimit);
        }
        if ($memoryLimit) {
            $pollingMetadata = $pollingMetadata->withMemoryLimitInMegabytes($memoryLimit);
        }
        if ($cron) {
            $pollingMetadata = $pollingMetadata->withCron($cron);
        }
        if ($finishWhenNoMessages) {
            $pollingMetadata = $pollingMetadata->withFinishWhenNoMessages(true);
        }


        $configuredMessagingSystem->run($consumerName, $pollingMetadata);
    }

    public function listAsynchronousEndpointsCommand(ConfiguredMessagingSystem $configuredMessagingSystem): ConsoleCommandResultSet
    {
        $consumers = [];
        foreach ($configuredMessagingSystem->list() as $consumerName) {
            $consumers[] = [$consumerName];
        }

        return ConsoleCommandResultSet::create(['Name'], $consumers);
    }
}
