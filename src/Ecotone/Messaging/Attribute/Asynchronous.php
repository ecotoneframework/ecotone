<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Attribute;

use Ecotone\Messaging\Support\Assert;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::TARGET_CLASS)]
class Asynchronous
{
    private string $channelName;

    public function __construct(string $channelName)
    {
        Assert::notNullAndEmpty($channelName, "Channel name can not be empty string");
        $this->channelName = $channelName;
    }

    public function getChannelName(): string
    {
        return $this->channelName;
    }
}