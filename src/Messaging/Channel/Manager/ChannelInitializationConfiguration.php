<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\Manager;

/**
 * Configuration for message channel initialization behavior.
 *
 * licence Apache-2.0
 */
final class ChannelInitializationConfiguration
{
    private function __construct(
        private bool $automaticChannelInitialization = true
    ) {
    }

    public static function createWithDefaults(): self
    {
        return new self();
    }

    /**
     * Controls whether message channels are automatically initialized on first use.
     * When set to false, channels must be created manually using `ecotone:migration:channel:setup`.
     */
    public function withAutomaticChannelInitialization(bool $enabled): self
    {
        $self = clone $this;
        $self->automaticChannelInitialization = $enabled;
        return $self;
    }

    public function isAutomaticChannelInitializationEnabled(): bool
    {
        return $this->automaticChannelInitialization;
    }
}
