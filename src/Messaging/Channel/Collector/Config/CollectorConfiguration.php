<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\Collector\Config;

use Ecotone\Messaging\Support\Assert;

/**
 * licence Apache-2.0
 */
final class CollectorConfiguration
{
    /**
     * @param string[] $togetherCollectedChannelNames
     */
    private function __construct(
        private array  $togetherCollectedChannelNames,
        private string $sendCollectedToMessageChannelName,
    ) {
        Assert::notNullAndEmpty($togetherCollectedChannelNames, 'At least one channel name must be provided for Collector.');
        Assert::notNullAndEmpty($sendCollectedToMessageChannelName, 'Targeted message channel name must be provided for Collector.');
    }

    /**
     * @param string[] $togetherCollectedChannelNames
     */
    public static function createWithOutboundChannel(array $togetherCollectedChannelNames, string $sendCollectedToMessageChannelName): self
    {
        return new self($togetherCollectedChannelNames, $sendCollectedToMessageChannelName);
    }

    public static function createWithDefaultProxy(array $togetherCollectedChannelNames): self
    {
        return new self($togetherCollectedChannelNames, CollectorModule::ECOTONE_COLLECTOR_DEFAULT_PROXY);
    }

    public function getSendCollectedToMessageChannelName(): string
    {
        return $this->sendCollectedToMessageChannelName;
    }

    /**
     * @return string[]
     */
    public function getTogetherCollectedChannelNames(): array
    {
        return $this->togetherCollectedChannelNames;
    }
}
