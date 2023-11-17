<?php

declare(strict_types=1);

namespace Ecotone\Modelling;

use Ecotone\Messaging\Message;

interface SaveAggregateService
{
    public function save(Message $message, array $metadata): Message;
}
