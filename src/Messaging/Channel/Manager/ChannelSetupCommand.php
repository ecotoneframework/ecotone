<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\Manager;

use Ecotone\Messaging\Attribute\ConsoleCommand;
use Ecotone\Messaging\Attribute\ConsoleParameterOption;
use Ecotone\Messaging\Config\ConsoleCommandResultSet;

use function is_bool;

/**
 * Console command for setting up message channels.
 *
 * licence Apache-2.0
 */
class ChannelSetupCommand
{
    public function __construct(
        private ChannelSetupManager $channelSetupManager
    ) {
    }

    #[ConsoleCommand('ecotone:migration:channel:setup')]
    public function setup(
        #[ConsoleParameterOption] array $channel = [],
        #[ConsoleParameterOption] bool|string $initialize = false,
    ): ?ConsoleCommandResultSet {
        // Normalize boolean parameters from CLI strings
        $initialize = $this->normalizeBoolean($initialize);

        // If specific channel names provided
        if (count($channel) > 0) {
            $rows = [];

            if ($initialize) {
                foreach ($channel as $channelName) {
                    $this->channelSetupManager->initialize($channelName);
                    $rows[] = [$channelName, 'Initialized'];
                }
                return ConsoleCommandResultSet::create(['Channel', 'Status'], $rows);
            }

            $status = $this->channelSetupManager->getInitializationStatus();
            foreach ($channel as $channelName) {
                $channelStatus = $status[$channelName] ?? false;
                $rows[] = [$channelName, $this->formatStatus($channelStatus)];
            }
            return ConsoleCommandResultSet::create(['Channel', 'Initialized'], $rows);
        }

        // Show all channels
        $channelNames = $this->channelSetupManager->getChannelNames();

        if (count($channelNames) === 0) {
            return ConsoleCommandResultSet::create(
                ['Status'],
                [['No message channels registered for setup.']]
            );
        }

        if ($initialize) {
            $this->channelSetupManager->initializeAll();
            return ConsoleCommandResultSet::create(
                ['Channel', 'Status'],
                array_map(fn (string $channel) => [$channel, 'Initialized'], $channelNames)
            );
        }

        // Show status
        $initializationStatus = $this->channelSetupManager->getInitializationStatus();
        $rows = [];
        foreach ($channelNames as $channelName) {
            $channelStatus = $initializationStatus[$channelName] ?? false;
            $rows[] = [$channelName, $this->formatStatus($channelStatus)];
        }

        return ConsoleCommandResultSet::create(['Channel', 'Initialized'], $rows);
    }

    /**
     * Normalize boolean parameter from CLI string to actual boolean.
     * Handles cases where CLI passes "false" as a string.
     */
    private function normalizeBoolean(bool|string $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        // Handle string values from CLI
        return $value !== 'false' && $value !== '0' && $value !== '';
    }

    /**
     * Format the initialization status for display
     */
    private function formatStatus(bool|string $status): string
    {
        if (is_string($status)) {
            return $status;
        }
        return $status ? 'Yes' : 'No';
    }
}
