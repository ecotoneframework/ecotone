<?php

declare(strict_types=1);

namespace Ecotone\Modelling;

use Ecotone\Messaging\Handler\MessageProcessor;
use Ecotone\Messaging\Message;

/**
 * licence Apache-2.0
 */
interface SaveAggregateService extends MessageProcessor
{
    public function process(Message $message): Message;
}
