<?php

declare(strict_types=1);

namespace Ecotone\Modelling;

use Ecotone\Messaging\Message;

/**
 * licence Apache-2.0
 */
interface SaveAggregateService
{
    public function save(Message $message, array $metadata): Message;
}
