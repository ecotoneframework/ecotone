<?php

declare(strict_types=1);

namespace Ecotone\Api\Messaging;

use Stringable;

/**
 * licence Apache-2.0
 */
interface MessageChannel extends Stringable
{
    public function send(Message $message): void;
}
