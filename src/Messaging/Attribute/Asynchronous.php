<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Attribute;

use Attribute;
use Ecotone\Messaging\Support\Assert;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
/**
 * licence Apache-2.0
 */
class Asynchronous
{
    private string|array $channelName;

    public function __construct(string|array $channelName)
    {
        Assert::notNullAndEmpty($channelName, 'Channel name can not be empty string');
        $this->channelName = $channelName;
    }

    public function getChannelName(): array
    {
        return is_string($this->channelName) ? [$this->channelName] : $this->channelName;
    }
}
