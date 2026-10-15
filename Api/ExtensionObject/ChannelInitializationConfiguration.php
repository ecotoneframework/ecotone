<?php

declare(strict_types=1);

namespace Ecotone\Api\ExtensionObject;

/**
 * licence Apache-2.0
 *
 * @link https://docs.ecotone.tech/modelling/migrations-storage#disabling-automatic-channel-initialization
 */
final class ChannelInitializationConfiguration
{
    private function __construct(
        public readonly bool $automaticChannelInitialization = true
    ) {
    }

    public static function createWithDefaults(): self
    {
        return new self();
    }

    public function withAutomaticChannelInitialization(bool $enabled): self
    {
        return new self(...[...get_object_vars($this), 'automaticChannelInitialization' => $enabled]);
    }
}
