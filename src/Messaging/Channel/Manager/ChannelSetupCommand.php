<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\Manager;

use Ecotone\Messaging\Attribute\ConsoleCommand;
use Ecotone\Messaging\Attribute\ConsoleParameterOption;
use Ecotone\Messaging\Config\ConsoleCommandResultSet;

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
        #[ConsoleParameterOption] array $channels = [],
        #[ConsoleParameterOption] bool $initialize = false,
    ): ?ConsoleCommandResultSet {
        // If specific channel names provided
        if (count($channels) > 0) {
            $rows = [];

            if ($initialize) {
                foreach ($channels as $channelName) {
                    $this->channelSetupManager->initialize($channelName);
                    $rows[] = [$channelName, 'Initialized'];
                }
                return ConsoleCommandResultSet::create(['Channel', 'Status'], $rows);
            }

            $status = $this->channelSetupManager->getInitializationStatus();
            foreach ($channels as $channelName) {
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
