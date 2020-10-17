<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Annotation;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::TARGET_CLASS)]
class Asynchronous
{
    public string $channelName;

    public function __construct(string $channelName)
    {
        $this->channelName = $channelName;
    }
}