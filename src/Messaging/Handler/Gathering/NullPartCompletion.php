<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Gathering;

use Ecotone\Api\Messaging\Message;

/**
 * licence Apache-2.0
 */
final class NullPartCompletion implements PartCompletion
{
    public function validateRoute(Message $request, array $destinations): void
    {
    }

    public function complete(Message $request, ?Message $reply): bool
    {
        return false;
    }
}
