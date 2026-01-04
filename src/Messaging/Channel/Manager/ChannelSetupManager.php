<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\Manager;

use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use InvalidArgumentException;

/**
 * Manages channel setup and teardown for all registered channel managers.
 * Similar to DatabaseSetupManager but for message channels.
 *
 * licence Apache-2.0
 */
class ChannelSetupManager implements DefinedObject
{
    /**
     * @param ChannelManager[] $channelManagers
     * @param string[] $allPollableChannelNames All pollable channel names (including non-managed ones)
     */
    public function __construct(
        private array $channelManagers = [],
        private array $allPollableChannelNames = [],
    ) {
    }

    /**
     * @return string[] List of all channel names (managed and non-managed)
     */
    public function getChannelNames(): array
    {
        $managedChannelNames = array_map(
            fn (ChannelManager $manager) => $manager->getChannelName(),
            $this->channelManagers
        );

        // Combine managed and non-managed channels, removing duplicates
        return array_values(array_unique(array_merge($managedChannelNames, $this->allPollableChannelNames)));
    }

    /**
     * Check if a channel is managed by migration system
     */
    private function isManagedChannel(string $channelName): bool
    {
        foreach ($this->channelManagers as $manager) {
            if ($manager->getChannelName() === $channelName) {
                return true;
            }
        }
        return false;
    }

    /**
     * Initialize all channels
     */
    public function initializeAll(): void
    {
        foreach ($this->channelManagers as $manager) {
            $manager->initialize();
        }
    }

    /**
     * Initialize specific channel by name
     */
    public function initialize(string $channelName): void
    {
        if (! $this->isManagedChannel($channelName)) {
            throw new InvalidArgumentException(
                "Channel '{$channelName}' is not managed by the migration system. " .
                'Only channels registered via ChannelManagerReference can be initialized through this command.'
            );
        }

        $manager = $this->findManager($channelName);
        $manager->initialize();
    }

    /**
     * Delete all channels
     */
    public function deleteAll(): void
    {
        foreach ($this->channelManagers as $manager) {
            $manager->delete();
        }
    }

    /**
     * Delete specific channel by name
     */
    public function delete(string $channelName): void
    {
        if (! $this->isManagedChannel($channelName)) {
            throw new InvalidArgumentException(
                "Channel '{$channelName}' is not managed by the migration system. " .
                'Only channels registered via ChannelManagerReference can be deleted through this command.'
            );
        }

        $manager = $this->findManager($channelName);
        $manager->delete();
    }

    /**
     * Returns initialization status for each channel
     * @return array<string, bool|string> Map of channel name to initialization status (bool for managed, 'Not managed by migration' for non-managed)
     */
    public function getInitializationStatus(): array
    {
        $status = [];

        foreach ($this->channelManagers as $manager) {
            $status[$manager->getChannelName()] = $manager->isInitialized();
        }

        foreach ($this->allPollableChannelNames as $channelName) {
            if (! isset($status[$channelName])) {
                $status[$channelName] = 'Not managed by migration';
            }
        }

        return $status;
    }

    private function findManager(string $channelName): ChannelManager
    {
        foreach ($this->channelManagers as $manager) {
            if ($manager->getChannelName() === $channelName) {
                return $manager;
            }
        }

        throw new InvalidArgumentException("Channel manager not found for channel: {$channelName}");
    }

    public function getDefinition(): Definition
    {
        $channelManagerDefinitions = array_map(
            fn (ChannelManager $manager) => $manager->getDefinition(),
            $this->channelManagers
        );

        return new Definition(
            self::class,
            [$channelManagerDefinitions, $this->allPollableChannelNames]
        );
    }
}
