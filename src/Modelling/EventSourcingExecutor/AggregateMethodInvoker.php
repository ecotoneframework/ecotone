<?php

declare(strict_types=1);

namespace Ecotone\Modelling\EventSourcingExecutor;

use Ecotone\Api\Messaging\Message;
use Ecotone\Modelling\ApplyMethod;

/**
 * licence Apache-2.0
 */
interface AggregateMethodInvoker
{
    public function executeMethod(mixed $aggregate, ApplyMethod $applyMethod, Message $message): void;
}
