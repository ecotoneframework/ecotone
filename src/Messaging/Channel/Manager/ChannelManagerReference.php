<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\Manager;

/**
 * Reference to a ChannelManager service registered in the container.
 * Broker modules register their ChannelManager implementations as services
 * and provide this reference so ChannelSetupModule can collect them.
 *
 * licence Apache-2.0
 */
final class ChannelManagerReference
{
    public function __construct(
        private string $referenceName,
    ) {
    }

    public function getReferenceName(): string
    {
        return $this->referenceName;
    }
}
