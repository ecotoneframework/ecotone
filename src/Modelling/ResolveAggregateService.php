<?php

declare(strict_types=1);

namespace Ecotone\Modelling;

use Ecotone\Messaging\Message;

interface ResolveAggregateService
{
    public function resolve(Message $message, array $metadata): Message;
}
