<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\AsyncPublishing;

/**
 * licence Enterprise
 */
final class ConfirmedDelivery implements PendingDelivery
{
    public function awaitDelivery(): DeliveryResult
    {
        return DeliveryResult::successful();
    }

    public function isAwaited(): bool
    {
        return true;
    }
}
