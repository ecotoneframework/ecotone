<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\Collector;

use Ecotone\Api\Messaging\Message;

/**
 * licence Apache-2.0
 */
interface MessageCollectionObserver
{
    public function collected(Message $message): void;
}
