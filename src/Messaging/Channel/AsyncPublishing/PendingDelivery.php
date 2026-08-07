<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\AsyncPublishing;

/**
 * licence Enterprise
 */
interface PendingDelivery
{
    public function awaitDelivery(): DeliveryResult;

    public function isAwaited(): bool;
}
