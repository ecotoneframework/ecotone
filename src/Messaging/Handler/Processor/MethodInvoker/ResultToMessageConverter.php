<?php

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker;

use Ecotone\Messaging\Message;

/**
 * licence Apache-2.0
 */
interface ResultToMessageConverter
{
    public function convertToMessage(Message $requestMessage, mixed $result): ?Message;
}
