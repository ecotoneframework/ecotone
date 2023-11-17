<?php

declare(strict_types=1);

namespace Ecotone\Modelling;

use Ecotone\Messaging\Message;

interface CallAggregateService
{
    public function call(Message $message): ?Message;
}
