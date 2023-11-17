<?php

declare(strict_types=1);

namespace Ecotone\Modelling;

use Ecotone\Messaging\Message;

interface ResolveAggregateEventsService
{
    public function resolve(Message $message, array $metadata): Message;
}
