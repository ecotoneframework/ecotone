<?php

declare(strict_types=1);

namespace Ecotone\Api\ExtensionObject;

use DateTimeImmutable;
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
        public readonly bool $failOnNotFoundHandler,
        public readonly ?MediaType $pollableChannelMediaTypeConversion,
        public readonly string $channelToConvertOn,
        public readonly array $spiedChannelNames,
        public readonly bool $inMemoryConsumerPositionTracker,
        public readonly bool $databaseTablesSetUpOnBootstrap,
        public readonly ?DateTimeImmutable $timeAt,
    ) {
    }

    public static function createWithDefaults(): self
    {
        return new self(true, null, '', [], true, true, null);
    }

    public function withTimeAt(DateTimeImmutable $time): self
    {
        return new self(...[...get_object_vars($this), 'timeAt' => $time]);
    }

    public function withFailOnNotFoundHandler(bool $shouldFail): self
    {
        return new self(...[...get_object_vars($this), 'failOnNotFoundHandler' => $shouldFail]);
    }

    public function withMediaTypeConversion(string $channelName, MediaType $mediaType): self
    {
        Assert::notNullAndEmpty($channelName, 'Converted channel can not be empty');

        return (new self(...[...get_object_vars($this), 'pollableChannelMediaTypeConversion' => $mediaType, 'channelToConvertOn' => $channelName]))
            ->withSpyOnChannel($channelName);
    }

    public function withSpyOnChannel(string $channelName): self
    {
        if (in_array($channelName, $this->spiedChannelNames)) {
            return $this;
        }

        return new self(...[...get_object_vars($this), 'spiedChannelNames' => [...$this->spiedChannelNames, $channelName]]);
    }

    public function withInMemoryConsumerPositionTracker(bool $enabled): self
    {
        return new self(...[...get_object_vars($this), 'inMemoryConsumerPositionTracker' => $enabled]);
    }

    public function withDatabaseTablesSetUpOnBootstrap(bool $enabled): self
    {
        return new self(...[...get_object_vars($this), 'databaseTablesSetUpOnBootstrap' => $enabled]);
    }
}
