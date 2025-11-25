<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Ecotone\Projecting\Attribute;

use Attribute;
use Ecotone\Messaging\Attribute\StreamBasedSource;

#[Attribute]
class Projection extends StreamBasedSource
{
    protected const RUNNING_MODE_POLLING = 'polling';
    protected const RUNNING_MODE_EVENT_DRIVEN = 'event-driven';
    protected const RUNNING_MODE_EVENT_STREAMING = 'event-streaming';

    protected string $runningMode = self::RUNNING_MODE_EVENT_DRIVEN;

    public function __construct(
        public readonly string  $name,
        public readonly ?string $partitionHeaderName = null,
        public readonly bool    $automaticInitialization = true,
    ) {
    }

    public function isPolling(): bool
    {
        return $this->runningMode === self::RUNNING_MODE_POLLING;
    }

    public function isEventDriven(): bool
    {
        return $this->runningMode === self::RUNNING_MODE_EVENT_DRIVEN;
    }

    public function isEventStreaming(): bool
    {
        return $this->runningMode === self::RUNNING_MODE_EVENT_STREAMING;
    }
}
