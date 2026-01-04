<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\Manager;

use function count;

use Ecotone\Messaging\Attribute\ConsoleCommand;
use Ecotone\Messaging\Attribute\ConsoleParameterOption;
use Ecotone\Messaging\Config\ConsoleCommandResultSet;

/**
 * Console command for deleting message channels.
 *
 * licence Apache-2.0
 */
class ChannelDeleteCommand
{
    public function __construct(
        private ChannelSetupManager $channelSetupManager
    ) {
    }

    #[ConsoleCommand('ecotone:migration:channel:delete')]
    public function delete(
        #[ConsoleParameterOption] array $channels = [],
        #[ConsoleParameterOption] bool $force = false,
    ): ?ConsoleCommandResultSet {
        // If specific channel names provided
        if (count($channels) > 0) {
            $rows = [];

            if (! $force) {
                foreach ($channels as $channelName) {
                    $rows[] = [$channelName, 'Would be deleted (use --force to confirm)'];
                }
                return ConsoleCommandResultSet::create(['Channel', 'Warning'], $rows);
            }

            foreach ($channels as $channelName) {
                $this->channelSetupManager->delete($channelName);
                $rows[] = [$channelName, 'Deleted'];
            }
            return ConsoleCommandResultSet::create(['Channel', 'Status'], $rows);
        }

        // Show all channels
        $channelNames = $this->channelSetupManager->getChannelNames();

        if (count($channelNames) === 0) {
            return ConsoleCommandResultSet::create(
                ['Status'],
                [['No message channels registered for deletion.']]
            );
        }

        if (! $force) {
            return ConsoleCommandResultSet::create(
                ['Channel', 'Warning'],
                array_map(fn (string $channel) => [$channel, 'Would be deleted (use --force to confirm)'], $channelNames)
            );
        }

        $this->channelSetupManager->deleteAll();
        return ConsoleCommandResultSet::create(
            ['Channel', 'Status'],
            array_map(fn (string $channel) => [$channel, 'Deleted'], $channelNames)
        );
    }
}
