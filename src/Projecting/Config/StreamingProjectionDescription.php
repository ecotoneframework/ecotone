<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\Projecting\Config;

/**
 * @internal
 */
final class StreamingProjectionDescription
{
    public function __construct(public readonly string $projectionName, public readonly string $channelName)
    {
    }
}
