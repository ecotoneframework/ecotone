<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Ecotone\Projecting\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class EventStreamingProjection extends Projection
{
    public function __construct(
        string $name,
        public readonly string $streamingChannelName,
    ) {
        parent::__construct(
            name: $name,
            partitionHeaderName: null,
            automaticInitialization: true,
        );

        $this->runningMode = self::RUNNING_MODE_EVENT_STREAMING;
    }
}
