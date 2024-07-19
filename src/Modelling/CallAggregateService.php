<?php

declare(strict_types=1);

namespace Ecotone\Modelling;

use Ecotone\Messaging\Message;

/**
 * licence Apache-2.0
 */
interface CallAggregateService
{
    public function call(Message $message): ?Message;
}
