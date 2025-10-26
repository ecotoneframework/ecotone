<?php

declare(strict_types=1);

namespace Ecotone\Lite\Test;

use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Support\Assert;

/**
 * licence Apache-2.0
 */
final class TestConfiguration
{
    /**
     * @param string[] $spiedChannelNames
     */
    private function __construct(
        private bool $failOnCommandHandlerNotFound,
        private bool $failOnQueryHandlerNotFound,
        private ?MediaType $pollableChannelMediaTypeConversion,
        private string $channelToConvertOn,
        private array $spiedChannelNames,
        private bool $inMemoryConsumerPositionTracker,
    ) {
    }

    public static function createWithDefaults(): self
    {
        return new self(true, true, null, '', [], true);
    }

    public function withFailOnCommandHandlerNotFound(bool $shouldFail): self
    {
        $self = clone $this;
        $self->failOnCommandHandlerNotFound = $shouldFail;

        return $self;
    }

    public function withFailOnQueryHandlerNotFound(bool $shouldFail): self
    {
        $self = clone $this;
        $self->failOnQueryHandlerNotFound = $shouldFail;

        return $self;
    }

    public function withMediaTypeConversion(string $channelName, MediaType $mediaType): self
    {
        Assert::notNullAndEmpty($channelName, 'Converted channel can not be empty');

        $self = clone $this;
        $self->pollableChannelMediaTypeConversion = $mediaType;
        $self->channelToConvertOn = $channelName;

        return $self->withSpyOnChannel($channelName);
    }

    public function withSpyOnChannel(string $channelName): self
    {
        if (in_array($channelName, $this->spiedChannelNames)) {
            return $this;
        }

        $self = clone $this;
        $self->spiedChannelNames[] = $channelName;

        return $self;
    }

    public function isFailingOnCommandHandlerNotFound(): bool
    {
        return $this->failOnCommandHandlerNotFound;
    }

    public function isFailingOnQueryHandlerNotFound(): bool
    {
        return $this->failOnQueryHandlerNotFound;
    }

    public function getPollableChannelMediaTypeConversion(): ?MediaType
    {
        return $this->pollableChannelMediaTypeConversion;
    }

    public function getChannelToConvertOn(): string
    {
        return $this->channelToConvertOn;
    }

    public function getSpiedChannels(): array
    {
        return $this->spiedChannelNames;
    }

    public function withInMemoryConsumerPositionTracker(bool $enabled): self
    {
        $self = clone $this;
        $self->inMemoryConsumerPositionTracker = $enabled;

        return $self;
    }

    public function isInMemoryConsumerPositionTrackerEnabled(): bool
    {
        return $this->inMemoryConsumerPositionTracker;
    }
}
