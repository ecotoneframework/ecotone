<?php

namespace Ecotone\Api\Messaging;

use Stringable;

/**
 * licence Apache-2.0
 */
interface Message extends Stringable
{
    public function getHeaders(): MessageHeaders;

    public function getPayload(): mixed;
}
